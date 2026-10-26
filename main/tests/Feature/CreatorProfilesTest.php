<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\InstructorApplication;
use App\Models\TowerLevel;
use App\Models\User;
use App\Services\Account\InstructorApplications;
use App\Services\Administration\CreatorBadges;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function approvedBadgeProof(User $creator): InstructorApplication
{
    $proof = pendingInstructor($creator);
    $proof->update(['verification_status' => 'Approved']);

    return $proof;
}

function badgeData(array $overrides = []): array
{
    return array_replace(['record_version' => 0, 'decision' => 'grant', 'current_password' => 'password',
        'confirmed' => true, 'qualifications_reviewed' => true, 'reason' => 'Checked the awarding institution and relevant qualifications.',
        'application_id' => 1, 'application_version' => 1], $overrides);
}

test('creator public details are explicit escaped and never disclose private identity or application fields', function () {
    $creator = moduleAccount(Role::Instructor);
    approvedBadgeProof($creator);
    moduleSignIn($this, $creator);
    $this->put(route('creator-profile.update'), ['record_version' => 0, 'headline' => 'Software developer',
        'bio' => '<script>alert(1)</script>', 'accomplishments' => 'Teaching programming for five years.', 'confirmed' => true])->assertRedirect();
    $this->get(route('creators.show', $creator))->assertOk()->assertSee('Software developer')->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert', false)->assertDontSee($creator->email)->assertDontSee('instructor-credentials')->assertDontSee('Qualifications reviewed');
    $this->putJson(route('creator-profile.update'), ['record_version' => 0, 'confirmed' => true])->assertUnprocessable();
    $this->putJson(route('creator-profile.update'), ['record_version' => 1, 'confirmed' => true, 'verified' => true])->assertJsonValidationErrors('verified');
    $this->post(route('logout'));
    $this->get(route('creators.show', $creator))->assertOk()->assertDontSee($creator->email)->assertDontSee($creator->first_name ?? 'PRIVATE-NAME');
});

test('public creator profiles hide inactive identities and exclude draft and withdrawn content', function () {
    $module = moduleFixture(true);
    $creator = $module->creator;
    $this->get(route('creators.show', $creator))->assertOk()->assertSee($module->publishedRevision->title);
    $module->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->get(route('creators.show', $creator))->assertOk()->assertDontSee($module->publishedRevision->title);
    $creator->forceFill(['account_status' => AccountStatus::Suspended])->save();
    $this->get(route('creators.show', $creator))->assertNotFound();
    $this->get(route('creators.show', moduleAccount(Role::Learner)))->assertNotFound();
});

test('only current staff can grant reviewed creator badges with approved unchanged proof and an audited decision', function () {
    $creator = moduleAccount(Role::Instructor);
    $proof = approvedBadgeProof($creator);
    $moderator = moduleAccount(Role::Moderator);
    moduleSignIn($this, $moderator);
    $data = badgeData(['application_id' => $proof->id]);
    $this->putJson(route('creator-badges.update', $creator), array_replace($data, ['current_password' => 'wrong']))->assertUnprocessable();
    $this->putJson(route('creator-badges.update', $creator), array_replace($data, ['qualifications_reviewed' => false]))->assertUnprocessable();
    $this->putJson(route('creator-badges.update', $creator), array_replace($data, ['application_version' => 99]))->assertUnprocessable();
    $this->put(route('creator-badges.update', $creator), $data)->assertRedirect();
    expect($creator->fresh()->hasCreatorBadge())->toBeTrue();
    $this->get(route('creators.show', $creator))->assertOk()->assertSee('Qualifications reviewed');
    $this->putJson(route('creator-badges.update', $creator), $data)->assertUnprocessable();
    $this->put(route('creator-badges.update', $creator), array_replace($data, ['record_version' => 1, 'decision' => 'revoke']))->assertRedirect();
    expect($creator->fresh()->hasCreatorBadge())->toBeFalse();
    expect(DB::table('audit_events')->whereIn('event', ['creator_badge.granted', 'creator_badge.revoked'])->count())->toBe(2);
});

test('badge decisions reject missing proof self review and replaced or demoted reviewer sessions', function () {
    $creator = moduleAccount(Role::Instructor);
    $moderator = moduleAccount(Role::Moderator);
    moduleSignIn($this, $moderator);
    $this->putJson(route('creator-badges.update', $creator), badgeData())->assertUnprocessable();
    approvedBadgeProof($creator);
    $sessionId = session()->getId();
    $moderator->forceFill(['account_role' => Role::Learner])->save();
    expect(fn () => app(CreatorBadges::class)->decide($moderator, $sessionId, $creator, badgeData()))->toThrow(AuthorizationException::class);
    moduleSignIn($this, $creator);
    $this->get(route('creator-badges.show', $creator))->assertForbidden();
    $this->get(route('creator-profile.edit'))->assertOk();
    $this->assertDatabaseCount('creator_verifications', 0);
});

test('badge grants reject unavailable credential storage with a validation error', function (string $disk) {
    $creator = moduleAccount(Role::Instructor);
    $proof = approvedBadgeProof($creator);
    $proof->forceFill(['credential_disk' => $disk])->save();
    moduleSignIn($this, moduleAccount(Role::Moderator));
    $this->putJson(route('creator-badges.update', $creator), badgeData(['application_id' => $proof->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('qualifications_reviewed');
    $this->assertDatabaseCount('creator_verifications', 0);
    $this->assertDatabaseCount('audit_events', 0);
})->with(['public', 'undefined-proof']);

test('creator bylines appear on published content and a revoked badge disappears immediately', function () {
    $module = moduleFixture(true);
    $creator = $module->creator;
    $proof = approvedBadgeProof($creator);
    $moderator = moduleAccount(Role::Moderator);
    moduleSignIn($this, $moderator);
    $this->put(route('creator-badges.update', $creator), badgeData(['application_id' => $proof->id]))->assertRedirect();
    $learner = moduleAccount(Role::Learner);
    moduleSignIn($this, $learner);
    $this->get(route('learning.catalog'))->assertOk()->assertSee($creator->username)->assertSee('Qualifications reviewed');
    $this->get(route('modules.show', $module))->assertOk()->assertSee(route('creators.show', $creator), false);
    $this->get(route('creator-profile.edit'))->assertForbidden();
});

test('account deletion removes public profile badge and review data before private proof erasure', function () {
    $creator = moduleAccount(Role::Instructor);
    $proof = approvedBadgeProof($creator);
    $moderator = moduleAccount(Role::Moderator);
    moduleSignIn($this, $moderator);
    $this->put(route('creator-badges.update', $creator), badgeData(['application_id' => $proof->id]))->assertRedirect();
    moduleSignIn($this, $creator);
    $this->put(route('creator-profile.update'), ['record_version' => 0, 'headline' => 'Private test profile', 'confirmed' => true])->assertRedirect();
    $this->seed(TowerLevelSeeder::class);
    $this->post(route('review-queue.update', TowerLevel::where('position', 1)->firstOrFail()), ['action' => 'add'])->assertRedirect();
    $this->post(route('account.delete.store'), deletionData($creator))->assertRedirect(route('login'));
    $this->assertDatabaseCount('creator_profiles', 0);
    $this->assertDatabaseCount('creator_verifications', 0);
    $this->assertDatabaseCount('tower_review_items', 0);
    $this->get(route('creators.show', $creator))->assertNotFound();
});

test('retained Instructor qualification versions stay private scoped and audited', function () {
    $proof = pendingInstructor();
    app(InstructorApplications::class)->snapshot($proof);
    $other = pendingInstructor();
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('instructor-reviews.version-credential', [$proof, 1]))->assertForbidden();
    $moderator = moduleAccount(Role::Moderator);
    moduleSignIn($this, $moderator);
    $this->get(route('instructor-reviews.show', $proof))->assertOk()->assertSee('Submitted qualification history');
    $this->get(route('instructor-reviews.version-credential', [$other, 1]))->assertNotFound();
    $this->get(route('instructor-reviews.version-credential', [$proof, 1]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    expect(DB::table('audit_events')->where('event', 'instructor_application.credential_downloaded')->count())->toBe(1);
});
