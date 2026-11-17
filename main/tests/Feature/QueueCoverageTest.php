<?php

use App\Enums\Role;
use App\Jobs\Operations\WorkerHeartbeat;
use App\Services\Operations\Heartbeats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('Supervisor and Compose examples consume every required application queue', function () {
    foreach (['ops/supervisor/kody-worker.conf.example', 'compose.yaml'] as $path) {
        preg_match_all('/--queue=([^\s]+)/', file_get_contents(base_path($path)), $matches);
        $consumed = array_unique(explode(',', implode(',', $matches[1])));
        expect(array_diff(array_values(app(Heartbeats::class)->workers()), $consumed))->toBeEmpty();
    }
});

test('each produced queue has a bounded processing probe and its own success state', function () {
    Http::preventStrayRequests();
    expect(Artisan::call('kody:queue-check'))->toBe(1);
    Artisan::call('kody:operations-heartbeat');
    Artisan::call('kody:operations-heartbeat');
    expect(DB::table('jobs')->pluck('queue')->sort()->values()->all())->toBe(['code-execution', 'default', 'notifications', 'payments']);
    foreach (['default', 'payments', 'notifications'] as $queue) {
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => $queue, '--once' => true, '--force' => true, '--sleep' => 0]);
    }
    expect(Artisan::call('kody:queue-check'))->toBe(1);
    expect(collect(app(Heartbeats::class)->snapshot())->keyBy('queue')['code-execution']['state'])->toBe('Unknown');
    Artisan::call('kody:operations-heartbeat');
    expect(DB::table('jobs')->where('queue', 'code-execution')->count())->toBe(1);
    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'code-execution', '--once' => true, '--force' => true, '--sleep' => 0]);
    expect(Artisan::call('kody:queue-check'))->toBe(0);
    $this->travel(4)->minutes();
    expect(Artisan::call('kody:queue-check'))->toBe(1);
    $this->travelBack();
    Http::assertNothingSent();
});

test('the real execution queue worker completes a synthetic provider-fake evaluation', function () {
    readyJudge();
    $submission = submitAttempt(challengeFixture(true), moduleAccount(Role::Learner));
    Http::fake(fn ($request) => $request->method() === 'POST'
        ? Http::response(['token' => (string) Str::uuid()], 201)
        : Http::response(['status' => ['id' => 3]]));
    for ($step = 0; $step < 8 && $submission->fresh()->completed_at === null; $step++) {
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'code-execution', '--once' => true, '--force' => true, '--sleep' => 0]);
    }
    expect($submission->fresh()->status)->toBe('Passed');
    expect(DB::table('jobs')->where('queue', 'code-execution')->count())->toBe(0);
    $this->assertDatabaseCount('failed_jobs', 0);
    Http::assertSentCount(4);
});

test('custom default queues are probed and duplicate physical queues are not probed twice', function () {
    config(['queue.connections.database.queue' => 'mail-local']);
    Artisan::call('kody:operations-heartbeat');
    expect(DB::table('jobs')->where('queue', 'mail-local')->count())->toBe(1);
    DB::table('jobs')->delete();
    DB::table('operations_heartbeats')->delete();
    config(['queue.connections.database.queue' => 'notifications']);
    Artisan::call('kody:operations-heartbeat');
    expect(DB::table('jobs')->pluck('queue')->sort()->values()->all())->toBe(['code-execution', 'notifications', 'payments']);
});

test('same-second dispatch and late retries cannot multiply or acknowledge the wrong probe', function () {
    $this->freezeTime();
    Artisan::call('kody:operations-heartbeat');
    $first = DB::table('operations_heartbeats')->where('component', 'worker')->value('probe_id');
    DB::table('jobs')->where('queue', 'default')->delete();
    (new WorkerHeartbeat('worker', $first))->handle();
    Artisan::call('kody:operations-heartbeat');
    $second = DB::table('operations_heartbeats')->where('component', 'worker')->value('probe_id');
    expect($first)->not->toBe($second);
    (new WorkerHeartbeat('worker', $first))->handle();
    Artisan::call('kody:operations-heartbeat');
    expect(DB::table('operations_heartbeats')->where('component', 'worker')->value('probe_id'))->toBe($second);
    expect(DB::table('jobs')->where('queue', 'default')->count())->toBe(1);
    (new WorkerHeartbeat)->handle(); // Retained legacy jobs cannot acknowledge the new probe either.
    expect(DB::table('operations_heartbeats')->where('component', 'worker')->value('probe_id'))->toBe($second);
});
