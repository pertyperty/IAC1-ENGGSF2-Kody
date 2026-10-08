<?php

// CLI-only fixtures: never loaded by the application or exposed as an HTTP route.
use App\Enums\Role;
use App\Models\ChallengeTestCase;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\LearningCourse;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\CreatorExamples;
use App\Services\Content\ModulePublishing;
use App\Services\Gamification\LearningProgression;
use App\Services\Transactions\WalletLedger;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../../../vendor/autoload.php';

try {
    $database = getenv('DB_DATABASE');
    if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing' || getenv('KODY_BROWSER_ISOLATED') !== '1'
        || ! is_string($database) || ! preg_match('/^kody_browser_[a-f0-9]{32}$/D', $database)
        || ! in_array(getenv('DB_HOST'), ['127.0.0.1', '::1'], true)) {
        throw new RuntimeException('Browser fixtures require an isolated loopback testing database.');
    }
    $app = require __DIR__.'/../../../bootstrap/app.php';
    if ($app->configurationIsCached()) {
        throw new RuntimeException('Clear configuration caches before browser verification.');
    }
    $app->make(Kernel::class)->bootstrap();
    if (! $app->environment('testing') || config('database.default') !== 'pgsql'
        || config('database.connections.pgsql.database') !== $database
        || config('database.connections.pgsql.host') !== getenv('DB_HOST')
        || config('services.google.enabled') || config('judge0.enabled') || config('xendit.enabled')) {
        throw new RuntimeException('Browser environment isolation failed.');
    }
    $operation = $argv[1] ?? '';
    if (in_array($operation, ['create', 'drop'], true)) {
        config(['database.connections.browser_admin' => array_replace(config('database.connections.pgsql'), ['database' => 'postgres'])]);
        $connection = DB::connection('browser_admin');
        // The runner creates a fresh unpredictable name, then drops only that owned database.
        $connection->statement($operation === 'create' ? 'CREATE DATABASE "'.$database.'"' : 'DROP DATABASE "'.$database.'" WITH (FORCE)');
    } elseif (in_array($operation, ['migrate', 'migrate-baseline'], true)) {
        $options = ['--force' => true];
        if ($operation === 'migrate-baseline') {
            $options += ['--realpath' => true, '--path' => array_values(array_filter(glob(database_path('migrations/*.php')),
                fn (string $path) => ! str_ends_with($path, '2026_10_05_000038_add_challenge_discovery_metadata.php')))];
        }
        if (Artisan::call('migrate', $options) !== 0) {
            throw new RuntimeException('Browser migrations failed.');
        }
    } elseif ($operation === 'seed') {
        if (User::exists()) {
            throw new RuntimeException('Fixtures require a newly migrated empty database.');
        }
        $accounts = [];
        foreach (['instructor' => Role::Instructor, 'moderator' => Role::Moderator, 'administrator' => Role::Administrator, 'learner' => Role::Learner, 'arcadelearner' => Role::Learner,
            'navlearner' => Role::Learner, 'navcontributor' => Role::Contributor, 'navinstructor' => Role::Instructor,
            'navmoderator' => Role::Moderator, 'navadministrator' => Role::Administrator, 'interactive' => Role::Learner,
            'stickylearner' => Role::Learner, 'usabilitylearner' => Role::Learner] as $name => $role) {
            $accounts[$name] = User::factory()->create(['username' => 'browser_'.$name, 'email' => $name.'@browser.example.test',
                'password' => Hash::make('BrowserStrong12!'), 'account_role' => $role,
                'active_session_hash' => hash('sha256', 'browser-seed-session'), 'active_session_expires_at' => now()->addHour()]);
        }
        $manifest = ['courses' => [], 'modules' => []];
        $publishing = app(ModulePublishing::class);
        $courses = app(CoursePublishing::class);
        foreach (config('curriculum') as $slug => $plan) {
            $ids = [];
            foreach ($plan['lessons'] as $example) {
                $module = $publishing->save($accounts['instructor'], 'browser-seed-session', app(CreatorExamples::class)->fields($example));
                $publishing->submit($accounts['instructor'], 'browser-seed-session', $module, 1);
                $publishing->review($accounts['moderator'], 'browser-seed-session', $module->fresh(), 2, 'Approved', null);
                $ids[] = $module->id;
                $manifest['modules'][$example] = $module->id;
            }
            $course = $courses->save($accounts['instructor'], 'browser-seed-session', $plan + ['module_ids' => $ids, 'sequential' => true]);
            $courses->submit($accounts['instructor'], 'browser-seed-session', $course, 1);
            $courses->review($accounts['moderator'], 'browser-seed-session', $course->fresh(), 2, 'Approved', null);
            $manifest['courses'][$slug] = ['id' => $course->id, 'slots' => $course->fresh()->publishedRevision->modules->pluck('id')->all()];
        }
        User::query()->update(['active_session_hash' => null, 'active_session_expires_at' => null]);
        echo json_encode($manifest, JSON_THROW_ON_ERROR);
    } elseif ($operation === 'seed-history') {
        $learner = User::where('email', 'learner@browser.example.test')->sole();
        $learner->forceFill(['active_session_hash' => hash('sha256', 'browser-history-session'), 'active_session_expires_at' => now()->addHour()])->save();
        $course = LearningCourse::where('title', config('curriculum.first-programs.title'))->sole();
        $slots = $course->publishedRevision->modules;
        $learning = app(CourseLearning::class);
        $solution = ['program' => ['right', 'right', 'up', 'right', 'right']];
        app(LearningProgression::class)->record($learner, 'browser-history-session', 'sequences', 'game', $solution);
        $learning->enroll($learner, 'browser-history-session', $course->id, $course->published_revision_id);
        $learning->lesson($learner, 'browser-history-session', $course->id, $slots[0]->id);
        $learning->markRead($learner, 'browser-history-session', $course->id, $slots[0]->id);
        $learning->complete($learner, 'browser-history-session', $course->id, $slots[1]->id, 'game', $solution);
        // Synthetic conserved test funding only; no provider purchase or launch readiness is enabled.
        DB::transaction(fn () => app(WalletLedger::class)->credit($learner->id, 'browser-drill-funding', 'Purchase', 50, 10000));
        VerificationDelivery::create(['id' => (string) Str::uuid(), 'user_id' => $learner->id, 'token_hash' => hash('sha256', str_repeat('a', 64)), 'token' => str_repeat('a', 64)]);
        $challenge = CodingChallenge::create(['created_by' => User::where('email', 'instructor@browser.example.test')->value('id')]);
        $revision = CodingChallengeRevision::create(['challenge_id' => $challenge->id, 'number' => 1, 'title' => 'Upgrade history quest',
            'description' => 'Add two values.', 'language' => 'python', 'difficulty' => 'Easy', 'rules' => 'Integer values.',
            'input_format' => 'Two integers.', 'output_format' => 'Their sum.', 'cpu_time_ms' => 1000, 'memory_kib' => 262144]);
        ChallengeTestCase::create(['revision_id' => $revision->id, 'position' => 1, 'input' => '2 3', 'expected_output' => '5', 'hidden' => true]);
    } elseif ($operation === 'check-upgrade') {
        $revision = CodingChallengeRevision::where('title', 'Upgrade history quest')->sole();
        $learner = User::where('email', 'learner@browser.example.test')->sole();
        if ($revision->category !== 'foundations' || $revision->tags !== []
            || VerificationDelivery::where('user_id', $learner->id)->sole()->token !== str_repeat('a', 64)
            || app(WalletLedger::class)->snapshot($learner->id)['balance'] !== 50
            || (int) DB::table('xp_totals')->where('user_id', $learner->id)->value('xp') !== 60
            || Artisan::call('kody:ledger-check') !== 0) {
            throw new RuntimeException('Upgrade/restore integrity check failed.');
        }
        echo 'Upgrade defaults, encrypted fixture, validated progress and ledger integrity passed.';
    } elseif ($operation === 'fingerprint') {
        $fingerprints = [];
        foreach (DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename") as $table) {
            if (! preg_match('/^[a-z_][a-z0-9_]*$/D', $table->tablename)) {
                throw new RuntimeException('Unexpected fixture table name.');
            }
            $fingerprints[$table->tablename] = DB::selectOne('SELECT count(*) AS rows, md5(coalesce(string_agg(row_data, \'\' ORDER BY row_data), \'\')) AS digest FROM (SELECT to_jsonb(t)::text AS row_data FROM "'.$table->tablename.'" t) records');
        }
        echo json_encode($fingerprints, JSON_THROW_ON_ERROR);
    } elseif ($operation === 'verify-token') {
        $email = $argv[2] ?? '';
        if (! preg_match('/^[a-z0-9_-]+@browser\.example\.test$/D', $email)) {
            throw new RuntimeException('Only synthetic browser accounts may use the test inbox.');
        }
        $user = User::where('email', $email)->firstOrFail();
        echo VerificationDelivery::where('user_id', $user->id)->sole()->token;
    } elseif ($operation === 'snapshot') {
        $user = User::where('email', $argv[2] ?? '')->firstOrFail();
        echo json_encode(['xp' => (int) DB::table('xp_totals')->where('user_id', $user->id)->value('xp'),
            'xp_awards' => DB::table('xp_awards')->where('user_id', $user->id)->count(),
            'levels' => DB::table('learning_level_completions')->where('user_id', $user->id)->count(),
            'completed_lessons' => DB::table('course_module_progress')->whereIn('enrollment_id', DB::table('course_enrollments')->select('id')->where('user_id', $user->id))->whereNotNull('completed_at')->count(),
            'streak' => DB::table('learning_progress')->where('user_id', $user->id)->value('current_streak')], JSON_THROW_ON_ERROR);
    } else {
        throw new RuntimeException('Unknown browser fixture operation.');
    }
} catch (Throwable $exception) {
    // Do not emit SQL bindings, tokens or inherited provider/database configuration.
    fwrite(STDERR, 'Isolated browser fixture failed ('.get_class($exception).").\n");
    exit(1);
}
