<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ContributorApplication;
use App\Models\TowerLevel;
use App\Models\User;
use App\Services\Administration\ReviewInbox;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use App\Services\Games\TowerCurriculumAudit;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('Tower list keeps guest trials and account invitation without recording progress', function () {
    $this->seed(TowerLevelSeeder::class);
    $response = $this->get(route('home', ['view' => 'list', 'track' => 'expert']))->assertOk()->assertSee('Tower levels')
        ->assertSee('Beginner levels')->assertDontSee('Expert levels')->assertSee('Map view')
        ->assertHeader('Cache-Control', 'no-store, private');
    foreach ([1, 2, 3] as $position) {
        $response->assertSee('href="'.route('tower.show', TowerLevel::where('position', $position)->sole()).'"', false);
    }
    $response->assertDontSee('href="'.route('tower.show', TowerLevel::where('position', 4)->sole()).'"', false);
    $response->assertSee('href="'.route('register', ['from' => 'tower']).'"', false);
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('Tower list shares track locks and saved clears with the map and survives navigation', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    $user->forceFill(['last_login_at' => now()->subDay()])->save();
    moduleSignIn($this, $user);
    $first = TowerLevel::where('position', 71)->sole();
    $second = TowerLevel::where('position', 72)->sole();
    $url = fn ($level) => 'href="'.route('tower.show', $level).'"';
    $this->get(route('home', ['view' => 'list', 'track' => 'expert']))->assertOk()->assertSee('Expert levels')
        ->assertSee($url($first), false)->assertDontSee($url($second), false)->assertDontSee('Beginner levels');
    $this->get(route('home'))->assertSee('Expert levels')->assertSessionHas('tower.view', 'list');
    $this->get(route('tower.show', $second))->assertForbidden();
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->postJson(route('tower.complete', [$first, $first->current_revision_id, 0]), app(TowerCurriculumAudit::class)->solution($first->currentRevision->stages[0]))->assertOk();
    $this->get(route('home'))->assertSee('1 of 10 cleared')->assertSee($url($second), false)->assertSee('Cleared');
    $this->get(route('home', ['view' => 'map']))->assertSee('data-tower-map', false)->assertSessionHas('tower.view', 'map');
    $this->get(route('home', ['view' => ['list']]))->assertOk()->assertSee('data-tower-map', false);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('tower_clearances', 1);
});

test('Tower list handles unpublished and empty tracks and preserves role restrictions', function () {
    $this->seed(TowerLevelSeeder::class);
    TowerLevel::where('position', '>=', 71)->update(['current_revision_id' => null]);
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $this->get(route('home', ['view' => 'list', 'track' => 'expert']))->assertOk()->assertSee('No published levels');
    moduleSignIn($this, moduleAccount(Role::Contributor));
    $this->get(route('home', ['view' => 'list']))->assertRedirect(route('dashboard'));
});

test('staff inbox combines pending summaries and filters each kind without exposing private payloads', function (Role $role) {
    $module = moduleFixture(false, ['title' => '<script>Untrusted lesson</script>', 'content' => 'private-body-marker']);
    app(ModulePublishing::class)->submit(User::find($module->created_by), 'module-test-session', $module, 1);
    $course = courseFixture(false, overrides: ['title' => 'Pending course']);
    app(CoursePublishing::class)->submit(User::find($course->created_by), 'module-test-session', $course, 1);
    $challenge = challengeFixture(false, overrides: ['title' => 'Pending coding challenge']);
    app(ChallengePublishing::class)->submit(User::find($challenge->created_by), 'module-test-session', $challenge, 1);
    $instructor = pendingInstructor();
    $contributor = ContributorApplication::create(['user_id' => User::factory()->create()->id, 'request_message' => 'private-application-marker',
        'credential_disk' => 'local', 'credential_path' => 'private-qualification-marker.pdf', 'account_age_days' => 30,
        'completed_modules_count' => 25, 'completed_challenges_count' => 50, 'approval_status' => 'Pending']);
    moduleFixture(false, ['title' => 'Unsubmitted draft']);
    moduleFixture(true, ['title' => 'Already approved']);
    moduleSignIn($this, moduleAccount($role));
    $response = $this->get(route('review-inbox'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('5 pending items')->assertDontSee('Unsubmitted draft')->assertDontSee('Already approved')
        ->assertDontSee('private-body-marker')->assertDontSee('private-application-marker')->assertDontSee('private-qualification-marker.pdf')
        ->assertSee('&lt;script&gt;Untrusted lesson&lt;/script&gt;', false)->assertDontSee('<script>Untrusted lesson</script>', false);
    $targets = ['lessons' => $module, 'courses' => $course, 'challenges' => $challenge, 'instructors' => $instructor, 'contributors' => $contributor];
    foreach ($targets as $kind => $target) {
        $response->assertSee(route(ReviewInbox::CATEGORIES[$kind]['route'], $target), false);
        $filtered = $this->get(route('review-inbox', ['category' => $kind]))->assertOk()->assertSee('1 pending item');
        expect($filtered->viewData('items')->pluck('kind')->all())->toBe([$kind]);
    }
    expect(array_keys((array) $response->viewData('items')->first()))->toBe(['kind', 'target_id', 'title', 'username', 'number', 'submitted_at']);
    $this->getJson(route('review-inbox', ['category' => 'payments']))->assertUnprocessable()->assertJsonValidationErrors('category');
    $this->getJson(route('review-inbox', ['category' => ['lessons']]))->assertUnprocessable();
})->with([Role::Moderator, Role::Administrator]);

test('staff inbox excludes own, superseded and archived submissions and paginates deterministically', function () {
    $reviewer = moduleAccount(Role::Moderator);
    $superseded = moduleFixture(false, ['title' => 'Superseded submission']);
    $superseded->latestRevision->update(['review_status' => 'Pending']);
    $superseded->latestRevision->replicate()->fill(['number' => 2, 'title' => 'New draft', 'review_status' => 'Draft'])->save();
    $own = moduleFixture(false, ['title' => 'Own submission']);
    $own->update(['created_by' => $reviewer->id]);
    $own->latestRevision->update(['review_status' => 'Pending']);
    $archived = moduleFixture(true, ['title' => 'Archived submission']);
    $archived->update(['status' => 'Archived']);
    $archived->latestRevision->update(['review_status' => 'Pending']);
    for ($i = 1; $i <= 16; $i++) {
        $module = moduleFixture(false, ['title' => 'Queued lesson '.$i]);
        $module->latestRevision->update(['review_status' => 'Pending', 'updated_at' => now()->startOfDay()]);
    }
    moduleSignIn($this, $reviewer);
    $auditCount = DB::table('audit_events')->count();
    $response = $this->get(route('review-inbox', ['category' => 'lessons']))->assertOk()
        ->assertSee('16 pending items')->assertDontSee('Superseded submission')->assertDontSee('Own submission')->assertDontSee('Archived submission');
    expect($response->viewData('items')->count())->toBe(15);
    expect($response->viewData('items')->last()->title)->toBe('Queued lesson 15');
    $this->get(route('review-inbox', ['category' => 'lessons', 'page' => 2]))->assertOk()->assertSee('Queued lesson 16');
    $this->assertDatabaseCount('audit_events', $auditCount);
});

test('review inbox denies guests, participants and inactive or unverified staff', function () {
    $this->get(route('review-inbox'))->assertRedirect(route('login'));
    foreach ([Role::Learner, Role::Contributor, Role::Instructor] as $role) {
        moduleSignIn($this, moduleAccount($role));
        $this->get(route('review-inbox'))->assertForbidden();
    }
    $reviewer = moduleAccount(Role::Moderator);
    moduleSignIn($this, $reviewer);
    $reviewer->forceFill(['email_verified_at' => null, 'account_status' => AccountStatus::Unverified])->save();
    $this->get(route('review-inbox'))->assertRedirect(route('login'));
    $this->assertGuest();
    $reviewer = moduleAccount(Role::Moderator);
    moduleSignIn($this, $reviewer);
    $reviewer->forceFill(['account_status' => AccountStatus::Suspended])->save();
    $this->get(route('review-inbox'))->assertRedirect(route('login'));
    $this->assertGuest();
});
