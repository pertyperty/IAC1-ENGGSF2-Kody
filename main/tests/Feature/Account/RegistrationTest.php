<?php

use App\Actions\Account\RegisterAccount;
use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\EmailVerification;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Models\VerificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function registrationData(array $overrides = []): array
{
    return array_replace([
        'username' => 'learnerone', 'email' => 'learner@example.test',
        'first_name' => 'Maria', 'last_name' => 'Cruz',
        'password' => 'StrongPass12!', 'password_confirmation' => 'StrongPass12!',
        'account_type' => 'learner',
    ], $overrides);
}

test('A01 registration creates an unverified learner and durable delivery atomically', function () {
    $this->postJson(route('register.store'), registrationData(['email' => 'Learner@Example.test']))->assertCreated();

    $user = User::sole();
    expect($user->email)->toBe('learner@example.test')
        ->and($user->account_role)->toBe(Role::Learner)
        ->and($user->account_status)->toBe(AccountStatus::Unverified)
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('StrongPass12!', $user->password))->toBeTrue();
    $verification = EmailVerification::sole();
    $delivery = VerificationDelivery::sole();
    expect($verification->request_count)->toBe(1)
        ->and($verification->token_hash)->toBe(hash('sha256', $delivery->token))
        ->and($delivery->getRawOriginal('token'))->not->toContain($delivery->token);
    $this->assertDatabaseCount('jobs', 1);
    expect(DB::table('jobs')->value('payload'))->not->toContain($delivery->token);
    $this->assertGuest();
});

test('A01 rejects invalid registration input', function (array $override, string $field) {
    $this->postJson(route('register.store'), registrationData($override))->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('jobs', 0);
})->with([
    'short username' => [['username' => 'short'], 'username'],
    'long username' => [['username' => str_repeat('a', 31)], 'username'],
    'invalid email' => [['email' => 'invalid'], 'email'],
    'nonalphabetic name' => [['first_name' => 'Maria1'], 'first_name'],
    'short password' => [['password' => 'Short1!', 'password_confirmation' => 'Short1!'], 'password'],
    'long password' => [['password' => str_repeat('A', 30).'b1!', 'password_confirmation' => str_repeat('A', 30).'b1!'], 'password'],
    'missing symbols' => [['password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123'], 'password'],
    'missing numbers' => [['password' => 'StrongPassword!', 'password_confirmation' => 'StrongPassword!'], 'password'],
    'missing mixed case' => [['password' => 'strongpass123!', 'password_confirmation' => 'strongpass123!'], 'password'],
    'mismatched passwords' => [['password_confirmation' => 'Different123!'], 'password'],
    'privileged assignment' => [['account_role' => 'Admin'], 'account_role'],
    'active assignment' => [['account_status' => 'Active'], 'account_status'],
    'role spoofing' => [['role' => 'Administrator'], 'role'],
    'invalid account type' => [['account_type' => 'moderator'], 'account_type'],
]);

test('A01 duplicate email or username never creates another account', function () {
    User::factory()->create(['username' => 'learnerone', 'email' => 'learner@example.test']);
    $this->postJson(route('register.store'), registrationData())->assertJsonValidationErrors(['email', 'username']);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('jobs', 0);
});

test('A01 authenticated users cannot register another account', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('register.store'), registrationData())->assertRedirect();
    $this->assertDatabaseCount('users', 1);
});

test('A01 instructor applicants retain learner privileges and store credentials privately', function () {
    Storage::fake('local');
    $data = registrationData(['account_type' => 'instructor', 'institution_name' => 'University', 'specialization' => 'Programming']);
    $data['credential_document'] = UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    $this->postJson(route('register.store'), $data)->assertCreated();

    $application = InstructorApplication::sole();
    expect(User::sole()->account_role)->toBe(Role::Learner)
        ->and($application->verification_status)->toBe('Pending')
        ->and($application->credential_path)->not->toContain('proof.pdf')
        ->and($application->toArray())->not->toHaveKeys(['credential_disk', 'credential_path']);
    Storage::disk('local')->assertExists($application->credential_path);
    expect(Storage::disk('local')->path($application->credential_path))->not->toStartWith(public_path());
    if (PHP_OS_FAMILY !== 'Windows') {
        // Windows ACLs do not expose POSIX visibility through Flysystem's chmod adapter.
        expect(Storage::disk('local')->getVisibility($application->credential_path))->toBe('private');
    }
    $response = $this->get('/storage/'.$application->credential_path);
    expect(in_array($response->status(), [403, 404], true))->toBeTrue();
});

test('A01 rejects executable files disguised as teaching credentials', function () {
    $data = registrationData(['account_type' => 'instructor', 'institution_name' => 'University', 'specialization' => 'Programming']);
    $file = UploadedFile::fake()->createWithContent('proof.pdf', '<?php echo "malicious";');
    // Use real MIME detection; fake files otherwise report a MIME inferred from their name.
    $data['credential_document'] = new UploadedFile($file->getPathname(), 'proof.pdf', null, null, true);
    $this->postJson(route('register.store'), $data)->assertJsonValidationErrors('credential_document');
    $this->assertDatabaseCount('users', 0);
});

test('A01 a queue persistence failure rolls back account and token and cleans credentials', function () {
    Storage::fake('local');
    $queue = Mockery::mock();
    $queue->shouldReceive('push')->once()->andThrow(new RuntimeException('queue unavailable'));
    Queue::shouldReceive('connection')->with('database')->andReturn($queue);

    $data = registrationData(['account_type' => 'instructor', 'institution_name' => 'University', 'specialization' => 'Programming']);
    $file = UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n%%EOF");
    expect(fn () => app(RegisterAccount::class)->handle($data, $file))->toThrow(RuntimeException::class, 'queue unavailable');
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('email_verifications', 0);
    $this->assertDatabaseCount('verification_deliveries', 0);
    $this->assertDatabaseCount('instructor_applications', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('A01 registration endpoints are rate limited', function () {
    foreach (range(1, 6) as $attempt) {
        $this->postJson(route('register.store'), [])->assertUnprocessable();
    }
    $this->postJson(route('register.store'), [])->assertTooManyRequests();
});
