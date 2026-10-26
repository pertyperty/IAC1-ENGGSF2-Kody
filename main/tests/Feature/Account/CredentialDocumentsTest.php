<?php

use App\Enums\Role;
use App\Models\ContributorApplication;
use App\Services\Account\CredentialDocuments;
use App\Services\Account\InstructorApplications;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function credentialDocumentFixture(string $kind): array
{
    if ($kind === 'contributor') {
        Storage::fake('local');
        Storage::disk('local')->put('contributor-credentials/proof.pdf', 'private contributor proof');
        $application = ContributorApplication::create(['user_id' => moduleAccount(Role::Learner)->id,
            'request_message' => 'Synthetic qualification fixture', 'credential_disk' => 'local',
            'credential_path' => 'contributor-credentials/proof.pdf', 'account_age_days' => 30,
            'completed_modules_count' => 25, 'completed_challenges_count' => 50]);
    } else {
        $application = pendingInstructor();
        if ($kind === 'history') {
            app(InstructorApplications::class)->snapshot($application);
        }
    }
    $route = $kind === 'contributor' ? 'contributor-reviews.credential'
        : ($kind === 'history' ? 'instructor-reviews.version-credential' : 'instructor-reviews.credential');

    return [$application, route($route, $kind === 'history' ? [$application, 1] : [$application])];
}

test('A09 A10 credential streams retain private headers names and scoped audit records', function (string $kind) {
    [$application, $url] = credentialDocumentFixture($kind);
    $reviewer = signInReviewer($this);
    $expectedName = $kind === 'contributor' ? 'contributor-credential.pdf'
        : ($kind === 'history' ? 'instructor-credential-v1.pdf' : 'instructor-credential.pdf');
    $response = $this->get($url)->assertOk()->assertDownload($expectedName)
        ->assertHeader('Content-Type', 'application/octet-stream')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'no-store, private');
    expect($response->streamedContent())->toBe(Storage::disk('local')->get($application->credential_path));
    $event = DB::table('audit_events')->sole();
    expect($event->actor_id)->toBe($reviewer->id)->and($event->subject_user_id)->toBe($application->user_id)
        ->and($event->subject_id)->toBe((string) $application->id)
        ->and($event->event)->toBe(($kind === 'contributor' ? 'contributor' : 'instructor').'_application.credential_downloaded');
    expect(json_decode($event->context, true))->toBe($kind === 'history' ? ['application_version' => 1] : []);
    expect($event->context)->not->toContain($application->credential_path);
})->with(['instructor', 'contributor', 'history']);

test('A09 A10 invalid credential references fail closed without a download audit', function (string $kind, string $case) {
    [$application, $url] = credentialDocumentFixture($kind);
    $prefix = $kind === 'contributor' ? 'contributor-credentials/' : 'instructor-credentials/';
    $fields = match ($case) {
        'public disk' => ['credential_disk' => 'public'],
        'public alias' => ['credential_disk' => 'public-proof'],
        'missing disk' => ['credential_disk' => 'undefined-proof'],
        'wrong namespace' => ['credential_path' => 'other-credentials/proof.pdf'],
        'traversal' => ['credential_path' => $prefix.'../proof.pdf'],
        'backslash' => ['credential_path' => $prefix.'folder\\proof.pdf'],
        'missing object' => ['credential_path' => $prefix.'missing.pdf'],
    };
    config(['filesystems.disks.public-proof' => ['driver' => 'local', 'visibility' => 'public', 'root' => storage_path('app/public')]]);
    if ($kind === 'history') {
        DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)->update($fields);
    } else {
        $application->forceFill($fields)->save();
        expect(app(CredentialDocuments::class)->available($application))->toBeFalse();
    }
    signInReviewer($this);
    $this->get($url)->assertNotFound();
    $this->assertDatabaseCount('audit_events', 0);
})->with(['instructor', 'contributor', 'history'])->with([
    'public disk', 'public alias', 'missing disk', 'wrong namespace', 'traversal', 'backslash', 'missing object',
]);

test('A09 A10 the shared document service authorizes before resolving private storage', function () {
    [$application] = credentialDocumentFixture('contributor');
    $application->credential_disk = 'undefined-proof';
    expect(fn () => app(CredentialDocuments::class)->download(moduleAccount(Role::Learner), $application))
        ->toThrow(AuthorizationException::class);
    $this->assertDatabaseCount('audit_events', 0);
});
