<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('branded error pages close their sections and preserve the expected HTTP status', function (int $status) {
    config(['app.debug' => false]);
    Route::get('/__test/error-page', fn () => abort($status));
    $buffers = ob_get_level();
    $this->get('/__test/error-page')->assertStatus($status)
        ->assertSee($status >= 500 ? '<title>A little pause · Kody</title>' : '<title>'.$status.' · Kody </title>', false)
        ->assertSee('Back to Kody')->assertDontSee('@endsection', false);
    expect(ob_get_level())->toBe($buffers);
})->with([403, 404, 419, 429, 500, 503]);
