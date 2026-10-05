<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Administration\SystemReports;
use App\Services\Gamification\Achievements;
use App\Services\Transactions\WalletLedger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseMigrations::class);

afterEach(function () {
    // Report fixtures may contain durable content history protected by down migrations.
    $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
});

function reportFilters(string $type = 'accounts', array $overrides = []): array
{
    return array_replace(['type' => $type, 'from' => '2026-10-03', 'to' => '2026-10-03'], $overrides);
}

function reportCounts(array $report): array
{
    return array_column($report['rows'], 'count', 'metric');
}

test('G13 accounting and reward reports use exact posted units and Manila boundaries without private references', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 00:00:00', 'UTC'));
    $admin = moduleAccount(Role::Administrator);
    $learner = moduleAccount(Role::Learner);
    economyCredit($learner, 100, 10000);
    app(Achievements::class)->award($learner->id, 'private-test-reference', 20);
    $this->travelTo(CarbonImmutable::parse('2026-10-03 16:00:00', 'UTC'));
    DB::transaction(fn () => app(WalletLedger::class)->credit($learner->id, 'outside-window', 'Purchase', 50, 5000));
    app(Achievements::class)->award($learner->id, 'outside-window', 40);
    $admin->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    $economy = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('economy'));
    expect(reportCounts($economy)['Purchased wallet credits · KB'])->toBe(100)
        ->and(reportCounts($economy)['Purchased wallet backing · PHP centavos'])->toBe(10000);
    $rewards = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('rewards'));
    expect(reportCounts($rewards)['Validated XP grants · XP'])->toBe(20)
        ->and(reportCounts($rewards)['Unique XP grants · records'])->toBe(1);
    expect(json_encode([$economy['rows'], $rewards['rows']]))->not->toContain($learner->email, 'private-test-reference', 'outside-window');
    moduleSignIn($this, $admin);
    $this->get(route('system-reports', reportFilters('economy')))->assertOk()->assertSee('PHP centavos')->assertHeader('Cache-Control', 'no-store, private');
});

test('G07 reports exclude participant and Moderator actors and require a current verified Active Admin', function (Role $role) {
    $actor = moduleAccount($role);
    moduleSignIn($this, $actor);
    $this->get(route('system-reports', reportFilters()))->assertForbidden();
    expect(fn () => app(SystemReports::class)->generate($actor, session()->getId(), reportFilters()))->toThrow(AuthorizationException::class);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('G07 report routes require authentication and stale or restricted Admin sessions fail', function () {
    $this->get(route('system-reports'))->assertRedirect(route('login'));
    $admin = moduleAccount(Role::Administrator);
    $admin->forceFill(['active_session_expires_at' => now()->subSecond()])->save();
    expect(fn () => app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters()))->toThrow(AuthorizationException::class);
    $admin->forceFill(['account_status' => AccountStatus::Suspended, 'active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters()))->toThrow(AuthorizationException::class);
});

test('G07 reports apply Manila inclusive calendar dates and current account states without exposing personal data', function () {
    $admin = moduleAccount(Role::Administrator);
    $admin->forceFill(['created_at' => '2026-10-01 00:00:00'])->save();
    User::factory()->create(['username' => 'privateaccount', 'email' => 'private@example.test', 'created_at' => '2026-10-02 16:00:00']);
    User::factory()->create(['account_status' => AccountStatus::Suspended, 'created_at' => '2026-10-03 15:59:59']);
    User::factory()->create(['created_at' => '2026-10-03 16:00:00']);
    User::factory()->create(['created_at' => '2026-10-02 15:59:59']);
    moduleSignIn($this, $admin);
    $response = $this->get(route('system-reports', reportFilters()))->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertDontSee('privateaccount')->assertDontSee('private@example.test')->assertSee('current state')->assertSee('Finance');
    $counts = reportCounts($response->viewData('report'));
    expect($counts['Registered accounts by current status · Active'])->toBe(1)
        ->and($counts['Registered accounts by current status · Suspended'])->toBe(1)
        ->and($counts['Registered accounts by current role · Learner'])->toBe(2);
    expect(DB::table('users')->count())->toBe(5);
});

test('G07 report filter selections survive reopen and malformed or oversized windows cannot replace them', function () {
    $admin = moduleAccount(Role::Administrator);
    moduleSignIn($this, $admin);
    $this->get(route('system-reports', reportFilters('content')))->assertOk();
    $this->get(route('system-reports'))->assertOk()->assertViewHas('filters', reportFilters('content'));
    foreach ([['type' => 'financial'], ['from' => '2026-02-30'], ['to' => '2026-10-02'], ['from' => '2025-10-01']] as $invalid) {
        $this->getJson(route('system-reports', reportFilters('content', $invalid)))->assertUnprocessable();
    }
    expect(session('system_report_filters'))->toBe(reportFilters('content'));
});

test('G07 content reports aggregate stored current lifecycles without titles authors or revision content', function () {
    $admin = moduleAccount(Role::Administrator);
    $module = moduleFixture(true);
    $module->forceFill(['created_at' => '2026-10-03 00:00:00'])->save();
    $challenge = challengeFixture(true);
    $challenge->forceFill(['created_at' => '2026-10-03 00:00:00'])->save();
    $report = app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('content'));
    $counts = reportCounts($report);
    expect($counts['Modules created · Published'])->toBe(1)->and($counts['Challenges created · Published'])->toBe(1)
        ->and($counts['Courses created · Published'])->toBe(0);
    expect(json_encode($report['rows']))->not->toContain('created_by', 'revision_id', 'title');
});

test('G07 validated learning records count stored completions and exclude outside-window browser practice', function () {
    $admin = moduleAccount(Role::Administrator);
    $learner = moduleAccount(Role::Learner);
    foreach (['game', 'quiz'] as $kind) {
        DB::table('learning_activity_days')->insert(['id' => (string) Str::uuid(), 'user_id' => $learner->id, 'level' => 'sequence',
            'kind' => $kind, 'template_version' => 1, 'business_date' => '2026-10-03', 'validated_input' => '[]', 'completed_at' => '2026-10-03 00:00:00']);
    }
    DB::table('learning_activity_days')->insert(['id' => (string) Str::uuid(), 'user_id' => $learner->id, 'level' => 'sequence',
        'kind' => 'game', 'template_version' => 1, 'business_date' => '2026-10-04', 'validated_input' => '[]', 'completed_at' => '2026-10-03 16:00:00']);
    $counts = reportCounts(app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters('learning')));
    expect($counts)->toBe(['Recorded validated game completions' => 1, 'Recorded validated quiz completions' => 1,
        'Course enrollments' => 0, 'Completed course assignments' => 0]);
});

test('G07 coding attempt report counts durable outcomes without source feedback hidden tests or provider details', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-03 00:00:00 UTC'));
    readyJudge();
    $admin = moduleAccount(Role::Administrator);
    $challenge = challengeFixture(true);
    $attempt = submitAttempt($challenge, moduleAccount(Role::Learner));
    $attempt->forceFill(['submitted_at' => '2026-10-03 00:00:00'])->save();
    $service = app(SystemReports::class);
    $counts = reportCounts($service->generate($admin, 'module-test-session', reportFilters('execution')));
    expect($counts['Attempts submitted · Queued'])->toBe(1)->and($counts['Attempts submitted · Passed'])->toBe(0);
    evaluateAttempt($attempt);
    $report = $service->generate($admin, 'module-test-session', reportFilters('execution'));
    expect(reportCounts($report)['Attempts submitted · Passed'])->toBe(1);
    expect(json_encode($report['rows']))->not->toContain($attempt->source_code, 'source_code', 'feedback', 'revision_id');
});

test('G07 reports own a repeatable read PostgreSQL transaction that rejects writes', function () {
    $admin = moduleAccount(Role::Administrator);
    $checked = false;
    DB::listen(function ($query) use (&$checked) {
        if (! $checked && str_contains($query->sql, 'COUNT(*) AS total')) {
            $checked = true;
            expect(DB::selectOne('SHOW transaction_isolation')->transaction_isolation)->toBe('repeatable read')
                ->and(DB::selectOne('SHOW transaction_read_only')->transaction_read_only)->toBe('on');
            DB::table('users')->update(['first_name' => 'Forbidden']);
        }
    });
    expect(fn () => app(SystemReports::class)->generate($admin, 'module-test-session', reportFilters()))->toThrow(QueryException::class);
    expect($checked)->toBeTrue()->and($admin->fresh()->first_name)->toBe('Test')->and(DB::transactionLevel())->toBe(0);
});

test('G07 concurrent committed registrations cannot mix snapshots between account report groups', function () {
    $admin = moduleAccount(Role::Administrator);
    $admin->forceFill(['created_at' => '2026-10-01 00:00:00'])->save();
    config(['database.connections.report_writer' => config('database.connections.pgsql')]);
    $inserted = false;
    DB::listen(function ($query) use (&$inserted) {
        if (! $inserted && str_contains($query->sql, 'COUNT(*) AS total')) {
            $inserted = true;
            $attributes = User::factory()->make()->getAttributes();
            $attributes['created_at'] = '2026-10-03 00:00:00';
            DB::connection('report_writer')->table('users')->insert($attributes);
        }
    });
    try {
        $service = app(SystemReports::class);
        $counts = reportCounts($service->generate($admin, 'module-test-session', reportFilters()));
        expect($inserted)->toBeTrue()->and($counts['Registered accounts by current status · Active'])->toBe(0)
            ->and($counts['Registered accounts by current role · Learner'])->toBe(0);
        $next = reportCounts($service->generate($admin, 'module-test-session', reportFilters()));
        expect($next['Registered accounts by current status · Active'])->toBe(1)->and($next['Registered accounts by current role · Learner'])->toBe(1);
    } finally {
        DB::purge('report_writer');
    }
});
