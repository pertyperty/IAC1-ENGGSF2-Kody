<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('liveness boots without a database connection or session', function () {
    config([
        'session.driver' => 'database',
        'database.connections.pgsql.host' => '127.0.0.1',
        'database.connections.pgsql.port' => 1,
    ]);

    $this->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'ok'])
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertCookieMissing(config('session.cookie'));
});

test('readiness checks the database without creating a session', function () {
    DB::shouldReceive('select')->once()->with('SELECT 1')->andReturn([(object) ['result' => 1]]);

    $this->getJson('/ready')
        ->assertOk()
        ->assertExactJson(['status' => 'ok'])
        ->assertCookieMissing(config('session.cookie'));
});

test('readiness failures reveal no exception details even with debug enabled', function () {
    config(['app.debug' => true]);
    DB::shouldReceive('select')->once()->with('SELECT 1')
        ->andThrow(new RuntimeException('password=secret host=private SQL SELECT sensitive'));
    Log::shouldReceive('warning')->once()->with('Database readiness check failed.');

    $this->get('/ready')
        ->assertStatus(503)
        ->assertExactJson(['status' => 'unavailable'])
        ->assertHeader('Cache-Control', 'no-store, private');
});
