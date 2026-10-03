<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('foundation migrations and persistence work on PostgreSQL', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    $user = User::factory()->create();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
    config(['session.driver' => 'database']);
    $this->getJson('/ready')->assertOk()->assertExactJson(['status' => 'ok']);
    $this->assertDatabaseCount('sessions', 0);
});

test('database transactions roll back failed writes', function () {
    $userId = null;

    expect(function () use (&$userId) {
        DB::transaction(function () use (&$userId) {
            $userId = User::factory()->create()->id;

            throw new RuntimeException('Abort smoke transaction');
        });
    })->toThrow(RuntimeException::class, 'Abort smoke transaction');

    $this->assertDatabaseMissing('users', ['id' => $userId]);
});
