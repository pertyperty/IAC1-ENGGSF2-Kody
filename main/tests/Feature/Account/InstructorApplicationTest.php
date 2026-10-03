<?php

use App\Actions\Account\ReviewInstructorApplication;
use App\Enums\Role;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Account\InstructorApplications;
use App\Services\Administration\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function creatorApplicationData(array $overrides = []): array
{
    return array_replace(['record_version' => 0, 'institution_name' => 'Learning Institute', 'specialization' => 'Programming',
        'credential_document' => UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"), 'confirmed' => true], $overrides);
}

test('A06 Learners and Contributors submit private credentials without changing their role', function (Role $role) {
    Storage::fake('local');
    $user = User::factory()->create(['account_role' => $role]);
    moduleSignIn($this, $user);
    $this->get(route('instructor-application.create'))->assertOk()->assertSee('Become a learning creator');
    $this->post(route('instructor-application.store'), creatorApplicationData())->assertRedirect(route('instructor-application.create'));
    $application = InstructorApplication::sole();
    expect($application->user_id)->toBe($user->id)->and($application->verification_status)->toBe('Pending')
        ->and($user->fresh()->account_role)->toBe($role);
    Storage::disk('local')->assertExists($application->credential_path);
    expect(DB::table('instructor_application_versions')->sole()->credential_path)->toBe($application->credential_path);
    $this->get(route('instructor-application.create'))->assertOk()->assertDontSee($application->credential_path)
        ->assertDontSee('name="credential_document"', false);
    $this->assertDatabaseCount('audit_events', 1);
})->with([Role::Learner, Role::Contributor]);

test('A06 Instructor Moderator and Administrator cannot submit applications', function (Role $role) {
    Storage::fake('local');
    moduleSignIn($this, User::factory()->create(['account_role' => $role]));
    $this->get(route('instructor-application.create'))->assertForbidden();
    $this->post(route('instructor-application.store'), creatorApplicationData())->assertForbidden();
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([Role::Instructor, Role::Moderator, Role::Administrator]);

test('A06 rejected applicants resubmit with retained decisions and credential versions then require new approval', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Contributor);
    $service = app(InstructorApplications::class);
    $firstData = creatorApplicationData();
    $service->submit($user, 'module-test-session', $firstData, $firstData['credential_document']);
    $application = InstructorApplication::sole();
    $oldPath = $application->credential_path;
    $moderator = moduleAccount(Role::Moderator);
    app(ReviewInstructorApplication::class)->handle($moderator, 'module-test-session', $application, 1, 'Rejected', 'Provide clearer proof.', true);
    $next = creatorApplicationData(['record_version' => 2]);
    $service->submit($user, 'module-test-session', $next, $next['credential_document']);
    $application->refresh();
    expect($application->record_version)->toBe(3)->and($application->verification_status)->toBe('Pending')
        ->and($application->verification_notes)->toBeNull()->and($user->fresh()->account_role)->toBe(Role::Contributor)
        ->and($application->credential_path)->not->toBe($oldPath);
    Storage::disk('local')->assertExists($oldPath);
    $first = DB::table('instructor_application_versions')->where('application_version', 1)->sole();
    expect($first->verification_status)->toBe('Rejected')->and($first->verification_notes)->toBe('Provide clearer proof.')
        ->and($first->credential_path)->toBe($oldPath);
    app(ReviewInstructorApplication::class)->handle($moderator, 'module-test-session', $application, 3, 'Approved', null, true);
    expect($user->fresh()->account_role)->toBe(Role::Instructor)
        ->and(DB::table('instructor_application_versions')->where('application_version', 3)->value('verification_status'))->toBe('Approved');
});

test('A06 pending and stale submissions retain the original credentials and clean up new uploads', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $service = app(InstructorApplications::class);
    $data = creatorApplicationData();
    $service->submit($user, 'module-test-session', $data, $data['credential_document']);
    foreach ([0, 1] as $version) {
        $next = creatorApplicationData(['record_version' => $version]);
        expect(fn () => $service->submit($user, 'module-test-session', $next, $next['credential_document']))->toThrow(ValidationException::class);
    }
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
    $this->assertDatabaseCount('instructor_application_versions', 1);
});

test('A06 credential content validation and confirmation reject executable uploads and forged approval', function (array $override, string $field) {
    Storage::fake('local');
    moduleSignIn($this, User::factory()->create());
    $this->postJson(route('instructor-application.store'), creatorApplicationData($override))->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('instructor_applications', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    [['credential_document' => UploadedFile::fake()->createWithContent('proof.php', '<?php echo 1;')], 'credential_document'],
    [['confirmed' => false], 'confirmed'], [['verification_status' => 'Approved'], 'verification_status'],
    [['user_id' => 999], 'user_id'], [['institution_name' => str_repeat('a', 101)], 'institution_name'],
]);

test('A06 application audit failure rolls back history and cleans up its file', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Test failure.'));
    $data = creatorApplicationData();
    expect(fn () => app(InstructorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('instructor_applications', 0);
    $this->assertDatabaseCount('instructor_application_versions', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('A06 credential-history down migration refuses to destroy a retained version', function () {
    Storage::fake('local');
    $user = moduleAccount(Role::Learner);
    $data = creatorApplicationData();
    app(InstructorApplications::class)->submit($user, 'module-test-session', $data, $data['credential_document']);
    $migration = require database_path('migrations/2026_10_03_000015_add_instructor_application_versions.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Instructor credential history must be preserved. Roll back application code without reverting this migration.');
    $this->assertDatabaseCount('instructor_application_versions', 1);
});
