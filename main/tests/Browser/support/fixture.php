<?php

// CLI-only fixtures: never loaded by the application or exposed as an HTTP route.
use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeTestCase;
use App\Models\CodingChallenge;
use App\Models\CodingChallengeRevision;
use App\Models\InstructorApplication;
use App\Models\LearningCourse;
use App\Models\User;
use App\Models\VerificationDelivery;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\FaqManagement;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\CreatorExamples;
use App\Services\Content\ModulePublishing;
use App\Services\Engagement\ContentFeedback;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\WeeklyEvents;
use App\Services\Transactions\ContentAccess;
use App\Services\Transactions\WalletLedger;
use Database\Seeders\LocalRoleAccountsSeeder;
use Database\Seeders\LocalRoleApplicationDemoSeeder;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $connection->statement($operation === 'create' ? 'CREATE DATABASE "'.$database.'" ENCODING \'UTF8\' TEMPLATE template0 LC_COLLATE \'C\' LC_CTYPE \'C\'' : 'DROP DATABASE "'.$database.'" WITH (FORCE)');
    } elseif (in_array($operation, ['migrate', 'migrate-baseline'], true)) {
        $options = ['--force' => true];
        if ($operation === 'migrate-baseline') {
            $options += ['--realpath' => true, '--path' => array_values(array_filter(glob(database_path('migrations/*.php')),
                fn (string $path) => ! str_ends_with($path, '2026_10_05_000038_add_challenge_discovery_metadata.php') && ! str_contains(basename($path), '2026_10_10_') && ! str_contains(basename($path), '2026_10_11_') && ! str_contains(basename($path), '2026_10_12_') && ! str_contains(basename($path), '2026_10_18_') && ! str_contains(basename($path), '2026_10_25_')))];
        }
        if (Artisan::call('migrate', $options) !== 0) {
            throw new RuntimeException('Browser migrations failed.');
        }
        if ($operation === 'migrate') {
            (new TowerLevelSeeder)->run();
        }
    } elseif ($operation === 'seed') {
        if (User::exists()) {
            throw new RuntimeException('Fixtures require a newly migrated empty database.');
        }
        if (Schema::hasTable('tower_levels')) {
            (new TowerLevelSeeder)->run();
        }
        $accounts = [];
        foreach (['credinstructor' => Role::Instructor, 'credmoderator' => Role::Moderator, 'reviewlearner' => Role::Learner,
            'instructor' => Role::Instructor, 'moderator' => Role::Moderator, 'administrator' => Role::Administrator, 'learner' => Role::Learner, 'arcadelearner' => Role::Learner, 'tracklearner' => Role::Learner, 'expertlearner' => Role::Learner, 'experteditor' => Role::Administrator,
            'navlearner' => Role::Learner, 'navcontributor' => Role::Contributor, 'navinstructor' => Role::Instructor,
            'navmoderator' => Role::Moderator, 'navadministrator' => Role::Administrator, 'interactive' => Role::Learner,
            'towerlearner' => Role::Learner, 'toweradmin' => Role::Administrator, 'stickylearner' => Role::Learner, 'usabilitylearner' => Role::Learner, 'picturelearner' => Role::Learner, 'librarylearner' => Role::Learner, 'guidelearner' => Role::Learner,
            'guidewallet' => Role::Learner, 'guideinstructor' => Role::Instructor, 'guidecontributor' => Role::Contributor,
            'guidemoderator' => Role::Moderator, 'guideadministrator' => Role::Administrator,
            'questlearner' => Role::Learner, 'quizlearner' => Role::Learner, 'quizcreator' => Role::Instructor, 'questcreator' => Role::Contributor,
            'polishcreator' => Role::Instructor, 'polishlearner' => Role::Learner, 'polishadmin' => Role::Administrator,
            'insightcreator' => Role::Instructor, 'insightreader' => Role::Learner, 'siteadmin' => Role::Administrator, 'appeallearner' => Role::Learner] as $name => $role) {
            $accounts[$name] = User::factory()->create(['username' => 'browser_'.$name, 'email' => $name.'@browser.example.test',
                'password' => Hash::make('BrowserStrong12!'), 'account_role' => $role,
                'active_session_hash' => hash('sha256', 'browser-seed-session'), 'active_session_expires_at' => now()->addHour()]);
        }
        $manifest = ['courses' => [], 'modules' => []];
        if (Schema::hasTable('creator_profiles')) {
            $credentialPath = 'instructor-credentials/browser-'.Str::uuid().'.pdf';
            Storage::disk('local')->put($credentialPath, '%PDF-1.4 synthetic qualification for browser review only');
            InstructorApplication::create(['user_id' => $accounts['credinstructor']->id, 'institution_name' => 'Synthetic Teaching Institute',
                'specialization' => 'Programming', 'credential_disk' => 'local', 'credential_path' => $credentialPath,
                'verification_status' => 'Approved', 'reviewed_by' => $accounts['credmoderator']->id, 'verified_at' => now(), 'record_version' => 2]);
            $manifest['credCreator'] = $accounts['credinstructor']->id;
        }
        foreach (config('help.categories') as $category => $label) {
            app(FaqManagement::class)->save($accounts['administrator'], 'browser-seed-session', [
                'question' => 'How do I explore '.$label.'?', 'answer' => 'Use the relevant workspace destination. The Kody button replays the current page guide.',
                'category' => $category, 'record_version' => 1,
            ]);
        }
        $publishing = app(ModulePublishing::class);
        $courses = app(CoursePublishing::class);
        foreach (config('curriculum') as $slug => $plan) {
            $ids = [];
            foreach ($plan['lessons'] as $example) {
                $fields = app(CreatorExamples::class)->fields($example);
                if (($fields['assessment_kind'] ?? '') === 'none' && Schema::hasColumn('module_revisions', 'attachments')) {
                    $fields['attachments'] = array_map(fn ($extension) => new UploadedFile(database_path('fixtures/lesson-resources/programming-foundations.'.$extension), 'programming-foundations.'.$extension, null, null, true), ['pdf', 'docx', 'pptx']);
                }
                $module = $publishing->save($accounts['instructor'], 'browser-seed-session', $fields);
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
        if (Schema::hasColumn('users', 'profile_picture')) {
            $module = $publishing->save($accounts['instructor'], 'browser-seed-session', app(CreatorExamples::class)->fields('quiz-lab'));
            $publishing->submit($accounts['instructor'], 'browser-seed-session', $module, 1);
            $publishing->review($accounts['moderator'], 'browser-seed-session', $module->fresh(), 2, 'Approved', null);
            $manifest['modules']['quiz-lab'] = $module->id;
            $challenges = app(ChallengePublishing::class);
            $challenge = $challenges->save($accounts['questcreator'], 'browser-seed-session', config('challenge-examples')[array_key_first(config('challenge-examples'))]
                + ['language' => 'python', 'difficulty' => 'Easy', 'cpu_time_ms' => 1000, 'memory_kib' => 262144]);
            $challenges->submit($accounts['questcreator'], 'browser-seed-session', $challenge, 1);
            $challenges->review($accounts['moderator'], 'browser-seed-session', $challenge->fresh(), 2, 'Approved', null);
            $event = app(WeeklyEvents::class)->configure($accounts['moderator'], 'browser-seed-session', [
                'week' => app(WeeklyEvents::class)->weekStart()->toDateString(),
                'challenge_id' => $challenge->id, 'revision_id' => $challenge->fresh()->published_revision_id, 'record_version' => 0,
                'rules' => 'Three independent attempts for this isolated weekly quest.', 'confirmed' => true,
            ]);
            $manifest['weekly'] = $event->id;
            $manifest['challenge'] = $challenge->id;
        }
        if (Schema::hasTable('site_announcements')) {
            $module = $publishing->save($accounts['insightcreator'], 'browser-seed-session', app(CreatorExamples::class)->fields('sequences'));
            $publishing->submit($accounts['insightcreator'], 'browser-seed-session', $module, 1);
            $publishing->review($accounts['moderator'], 'browser-seed-session', $module->fresh(), 2, 'Approved', null);
            app(ContentAccess::class)->open($accounts['insightreader'], 'browser-seed-session', 'module', $module->id);
            app(ContentFeedback::class)->read($accounts['insightreader'], 'browser-seed-session', 'module', $module->id, true);
            app(LearningProgression::class)->recordModule($accounts['insightreader'], 'browser-seed-session', $module->id, $module->fresh()->published_revision_id, 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]);
            $accounts['appeallearner']->forceFill(['account_status' => AccountStatus::Suspended])->save();
            $originalStorage = storage_path();
            $app->useStoragePath(dirname(getenv('KODY_BROWSER_MANIFEST')).'/role-fixture-storage');
            try {
                app(LocalRoleAccountsSeeder::class)->run(app(AuditRecorder::class));
            } finally {
                $app->useStoragePath($originalStorage);
            }
            app(LocalRoleApplicationDemoSeeder::class)->prepare();
            // These are disposable, harness-owned identities only; real local passwords are never changed.
            foreach (['learner4@kody.local', 'learner5@kody.local'] as $email) {
                User::where('email', $email)->sole()->forceFill(['password' => Hash::make('BrowserStrong12!')])->save();
            }
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
        if (Schema::hasTable('module_read_completions')) {
            $learning->markRead($learner, 'browser-history-session', $course->id, $slots[0]->id);
        } else {
            // Reproduce the approved pre-amendment reading record without backfilling XP.
            DB::table('course_module_progress')->where('enrollment_id', DB::table('course_enrollments')->where('user_id', $learner->id)->value('id'))->where('assignment_id', $slots[0]->id)
                ->update(['completed_at' => now(), 'validated_input' => json_encode(['kind' => 'reading'])]);
        }
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
            || DB::table('tower_levels')->count() !== 80 || DB::table('tower_revisions')->count() !== 80
            || DB::table('module_revisions')->where('attachments', '!=', '[]')->exists()
            || VerificationDelivery::where('user_id', $learner->id)->sole()->token !== str_repeat('a', 64)
            || app(WalletLedger::class)->snapshot($learner->id)['balance'] !== 50
            || (int) DB::table('xp_totals')->where('user_id', $learner->id)->value('xp') !== 60
            || DB::table('module_read_completions')->exists()
            || DB::table('site_announcements')->count() !== 1
            || DB::table('site_announcements')->where('id', 1)->where('active', false)->where('record_version', 1)->count() !== 1
            || Artisan::call('kody:ledger-check') !== 0) {
            throw new RuntimeException('Upgrade/restore integrity check failed.');
        }
        echo 'Upgrade defaults, encrypted fixture, validated progress and ledger integrity passed.';
    } elseif (in_array($operation, ['fingerprint', 'fingerprint-legacy'], true)) {
        $fingerprints = [];
        foreach (DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename") as $table) {
            if (! preg_match('/^[a-z_][a-z0-9_]*$/D', $table->tablename)) {
                throw new RuntimeException('Unexpected fixture table name.');
            }
            $row = 'to_jsonb(t)';
            if ($operation === 'fingerprint-legacy') {
                $row .= match ($table->tablename) {
                    'users' => " - 'profile_picture'", 'module_revisions' => " - 'attachments'", 'coding_challenge_revisions' => " - 'category' - 'tags'",
                    'email_verifications' => " - 'otp_hash' - 'otp_expires_at' - 'otp_attempts'", 'verification_deliveries' => " - 'otp'", default => '',
                };
            }
            $fingerprints[$table->tablename] = DB::selectOne('SELECT count(*) AS rows, md5(coalesce(string_agg(row_data, \'\' ORDER BY row_data), \'\')) AS digest FROM (SELECT ('.$row.')::text AS row_data FROM "'.$table->tablename.'" t) records');
        }
        echo json_encode($fingerprints, JSON_THROW_ON_ERROR);
    } elseif ($operation === 'office-fixture') {
        $path = tempnam(sys_get_temp_dir(), 'kody-browser-document-');
        try {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Browser notes: instruction order matters.</w:t></w:r></w:p></w:body></w:document>');
            $zip->close();
            echo base64_encode(file_get_contents($path));
        } finally {
            unlink($path);
        }
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
