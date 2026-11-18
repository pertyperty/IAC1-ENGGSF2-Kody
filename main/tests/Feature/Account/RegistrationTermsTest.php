<?php

use App\Actions\Account\RegisterAccount;
use App\Models\User;
use App\Support\RegistrationTerms;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('A01 terms are public and registration asks for an unchecked agreement', function () {
    $this->get(route('legal.terms'))->assertOk()->assertSee('General Luna Road')->assertSee('Kody project team');
    $this->get(route('legal.privacy'))->assertOk()->assertSee('Privacy notice');
    $response = $this->get(route('register'))->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $checkbox = $dom->getElementById('terms_accepted');
    expect($checkbox->hasAttribute('checked'))->toBeFalse()->and($checkbox->hasAttribute('required'))->toBeTrue();
});

test('A01 registration rejects missing rejected and stale terms without partial records', function (array $override) {
    $this->postJson(route('register.store'), registrationData($override))->assertUnprocessable();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('verification_deliveries', 0);
    $this->assertDatabaseCount('jobs', 0);
})->with([
    [['terms_accepted' => null]], [['terms_accepted' => false]], [['terms_accepted' => 'no']],
    [['terms_version' => null]], [['terms_version' => '2026-10-18']],
    [['terms_accepted_at' => '2000-01-01']],
]);

test('A01 acceptance uses server time and version and is not public model data', function () {
    $this->travelTo(now()->startOfSecond());
    $this->postJson(route('register.store'), registrationData())->assertCreated();
    $user = User::sole();
    expect($user->terms_version)->toBe(RegistrationTerms::VERSION)
        ->and($user->terms_accepted_at->equalTo(now()))->toBeTrue()
        ->and($user->toArray())->not->toHaveKeys(['terms_version', 'terms_accepted_at']);
    $migration = require database_path('migrations/2026_11_18_000001_record_registration_terms_acceptance.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Retained terms acceptance');
});

test('A01 direct registration cannot bypass acceptance and legacy users gain no fabricated agreement', function () {
    $legacy = User::factory()->create();
    expect($legacy->terms_version)->toBeNull()->and($legacy->terms_accepted_at)->toBeNull();
    expect(fn () => app(RegisterAccount::class)->handle(registrationData(['terms_accepted' => false])))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('users', 1);
    expect(fn () => DB::transaction(fn () => DB::table('users')->where('id', $legacy->id)
        ->update(['terms_version' => RegistrationTerms::VERSION])))->toThrow(QueryException::class);
});
