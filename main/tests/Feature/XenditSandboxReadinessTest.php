<?php

use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Support\Facades\DB;

it('allows sandbox checkout only on a dedicated local database with a development key and live flags off', function () {
    $this->app->detectEnvironment(fn () => 'local');
    config([
        'app.url' => 'http://127.0.0.1:8015',
        'database.default' => 'pgsql',
        'database.connections.pgsql.url' => null,
        'database.connections.pgsql.host' => '127.0.0.1',
        'database.connections.pgsql.port' => '55439',
        'database.connections.pgsql.database' => 'kody_xendit_sandbox',
        'queue.connections.database.connection' => null,
        'xendit.sandbox_enabled' => true,
        'xendit.sandbox_return_url' => 'https://sandbox.example.test/done',
        'xendit.enabled' => false,
        'xendit.contract_verified' => false,
        'xendit.secret_key' => 'xnd_development_fake_for_tests',
        'xendit.callback_token' => str_repeat('test-token', 4),
        'xendit.business_id' => 'test-merchant',
    ]);
    DB::purge('pgsql');
    $gate = app(Readiness::class);
    expect($gate->sandboxAvailable())->toBeTrue()->and($gate->available())->toBeFalse();

    foreach ([
        'database.connections.pgsql.database' => 'kody_test',
        'database.connections.pgsql.port' => 5432,
        'database.connections.pgsql.host' => 'production.example.test',
        'queue.connections.database.connection' => 'financial_live',
        'xendit.secret_key' => 'xnd_production_fake_for_tests',
        'xendit.enabled' => true,
        'xendit.contract_verified' => true,
        'xendit.sandbox_return_url' => 'http://sandbox.example.test/done',
    ] as $key => $value) {
        $original = config($key);
        config([$key => $value]);
        DB::purge('pgsql');
        expect($gate->checkoutAvailable())->toBeFalse($key);
        config([$key => $original]);
    }
    $this->app->detectEnvironment(fn () => 'production');
    expect($gate->checkoutAvailable())->toBeFalse();
});
