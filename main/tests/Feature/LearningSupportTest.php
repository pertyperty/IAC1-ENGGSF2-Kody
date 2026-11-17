<?php

use App\Enums\Role;
use App\Jobs\Operations\WorkerHeartbeat;
use App\Models\TowerLevel;
use App\Services\Operations\Heartbeats;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('owned review lists are idempotent access checked and never issue clears rewards or streaks', function () {
    $this->seed(TowerLevelSeeder::class);
    $level = TowerLevel::where('position', 1)->firstOrFail();
    $locked = TowerLevel::where('position', 2)->firstOrFail();
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $this->post(route('review-queue.update', $locked), ['action' => 'add'])->assertForbidden();
    for ($i = 0; $i < 2; $i++) {
        $this->post(route('review-queue.update', $level), ['action' => 'add'])->assertRedirect();
    }
    $this->get(route('review-queue.index'))->assertOk()->assertSee($level->currentRevision->title);
    $this->assertDatabaseCount('tower_review_items', 1);
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('xp_awards', 0);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('review-queue.index'))->assertOk()->assertDontSee($level->currentRevision->title);
    $this->post(route('review-queue.update', $level), ['action' => 'remove'])->assertRedirect();
    $this->assertDatabaseCount('tower_review_items', 1);
    moduleSignIn($this, $user);
    $this->post(route('review-queue.update', $level), ['action' => 'remove'])->assertRedirect();
    $this->assertDatabaseCount('tower_review_items', 0);
});

test('scheduler and worker probes distinguish unknown recent and stale without accumulating pending probes', function () {
    expect(app(Heartbeats::class)->snapshot()[0]['state'])->toBe('Unknown');
    expect(Artisan::call('kody:operations-heartbeat'))->toBe(0);
    expect(Artisan::call('kody:operations-heartbeat'))->toBe(0);
    $this->assertDatabaseCount('jobs', 1);
    expect(app(Heartbeats::class)->snapshot()[0]['state'])->toBe('Recent');
    expect(app(Heartbeats::class)->snapshot()[1]['state'])->toBe('Unknown');
    (new WorkerHeartbeat)->handle();
    expect(app(Heartbeats::class)->snapshot()[1]['state'])->toBe('Recent');
    $this->travel(4)->minutes();
    expect(app(Heartbeats::class)->snapshot()[0]['state'])->toBe('Stale');
    expect(app(Heartbeats::class)->snapshot()[1]['state'])->toBe('Stale');
    expect(Artisan::call('kody:operations-heartbeat'))->toBe(0);
    expect(app(Heartbeats::class)->snapshot()[0]['state'])->toBe('Recent');
    expect(app(Heartbeats::class)->snapshot()[1]['state'])->toBe('Stale');
    $this->get('/up')->assertOk();
    $this->get('/ready')->assertOk();
    $this->travelBack();
});
