<?php

use App\Actions\Account\LoginAccount;
use App\Actions\Account\RegisterAccount;
use App\Actions\Account\ReviewInstructorApplication;
use App\Models\CodingChallenge;
use App\Models\ContributorApplication;
use App\Models\FaqEntry;
use App\Models\GamePreset;
use App\Models\InstructorApplication;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Models\WeeklyEvent;
use App\Services\Account\AccountArchival;
use App\Services\Account\AccountDeletion;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\ContributorApplications;
use App\Services\Account\EmailVerificationService;
use App\Services\Account\InstructorApplications;
use App\Services\Account\ProfileEditing;
use App\Services\Administration\AccountEnforcement;
use App\Services\Administration\ContentModeration;
use App\Services\Administration\FaqManagement;
use App\Services\Administration\ModeratorAppointments;
use App\Services\Administration\SupportProfileCorrections;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
use App\Services\Engagement\ContentFeedback;
use App\Services\Games\GamePresets;
use App\Services\Gamification\LearningProgression;
use App\Services\Gamification\WeeklyEvents;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $input = json_decode(Crypt::decryptString(file_get_contents($argv[2])), true, flags: JSON_THROW_ON_ERROR);
    if (isset($input['clock'])) {
        Carbon::setTestNow(CarbonImmutable::parse($input['clock']));
        CarbonImmutable::setTestNow(CarbonImmutable::parse($input['clock']));
    }
    DB::statement("SET application_name = 'kody-account-concurrency'");
    $session = app('session')->driver();
    $session->start();

    $result = match ($argv[1]) {
        'content-react' => (function () use ($input, $argv): string {
            $index = (int) $argv[3] - 1;
            app(ContentFeedback::class)->change(User::findOrFail($input['actor_ids'][$index]), 'module-test-session',
                'module', $input['module_id'], $input['version'], $input['reactions'][$index]);

            return 'saved';
        })(),
        'faq-change' => (function () use ($input, $argv): string {
            $actor = User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]);
            $entry = FaqEntry::findOrFail($input['entry_id']);
            if ($input['action'] === 'delete') {
                app(FaqManagement::class)->delete($actor, 'module-test-session', $entry, 1, true);
            } else {
                app(FaqManagement::class)->save($actor, 'module-test-session', $input['data'], $entry);
            }

            return 'changed';
        })(),
        'faq-create' => (function () use ($input, $argv): string {
            app(FaqManagement::class)->save(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session', $input['data']);

            return 'created';
        })(),
        'preset-change' => (function () use ($input, $argv): string {
            $actor = User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]);
            $preset = GamePreset::findOrFail($input['preset_id']);
            if ($input['action'] === 'inactivate') {
                app(GamePresets::class)->inactivate($actor, 'module-test-session', $preset, 1, true);
            } else {
                app(GamePresets::class)->save($actor, 'module-test-session', $input['data'], $preset);
            }

            return 'changed';
        })(),
        'preset-create' => (function () use ($input, $argv): string {
            app(GamePresets::class)->save(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session', $input['data']);

            return 'created';
        })(),
        'content-moderate' => (function () use ($input, $argv): string {
            app(ContentModeration::class)->change(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session',
                $input['kind'], $input['content_id'], $input['data']);

            return 'moderated';
        })(),
        'support-correct' => (function () use ($input, $argv): string {
            app(SupportProfileCorrections::class)->correct(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session',
                User::findOrFail($input['target_id']), $input['data']);

            return 'corrected';
        })(),
        'moderator-change' => (function () use ($input, $argv): string {
            app(ModeratorAppointments::class)->change(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session',
                User::findOrFail($input['target_id']), $input['data']);

            return 'changed';
        })(),
        'account-enforce' => (function () use ($input, $argv): string {
            app(AccountEnforcement::class)->change(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session',
                User::findOrFail($input['target_id']), $input['data']);

            return 'enforced';
        })(),
        'contributor-review' => (function () use ($input, $argv): string {
            app(ContributorApplications::class)->review(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session',
                ContributorApplication::findOrFail($input['application_id']), $input['data']);

            return 'reviewed';
        })(),
        'role-apply' => (function () use ($input, $argv): string {
            config(['filesystems.disks.local.root' => $input['storage_root']]);
            $credential = UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n%%EOF");
            $actor = User::findOrFail($input['actor_id']);
            if ($input['mixed'] && (int) $argv[3] === 2) {
                app(InstructorApplications::class)->submit($actor, 'module-test-session', $input['instructor_data'], $credential);
            } else {
                app(ContributorApplications::class)->submit($actor, 'module-test-session', $input['data'], $credential);
            }

            return 'applied';
        })(),
        'account-delete' => (function () use ($input): string {
            try {
                app(AccountDeletion::class)->delete(User::findOrFail($input['actor_id']), $input['session_id'], $input['data']);

                return 'deleted';
            } catch (AuthorizationException) {
                return 'revoked';
            }
        })(),
        'account-archive' => (function () use ($input): string {
            try {
                app(AccountArchival::class)->archive(User::findOrFail($input['actor_id']), $input['session_id'], $input['data']);

                return 'archived';
            } catch (AuthorizationException) {
                return 'revoked';
            }
        })(),
        'creator-apply' => (function () use ($input): string {
            config(['filesystems.disks.local.root' => $input['storage_root']]);
            app(InstructorApplications::class)->submit(User::findOrFail($input['actor_id']), $input['session_id'], $input['data'],
                UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\n%%EOF"));

            return 'applied';
        })(),
        'profile-edit' => app(ProfileEditing::class)->update(User::findOrFail($input['actor_id']), $input['session_id'], $input['data']),
        'weekly-configure' => app(WeeklyEvents::class)->configure(User::findOrFail($input['actor_ids'][(int) $argv[3] - 1]), 'module-test-session', $input['data']) ? 'configured' : 'error',
        'weekly-sync' => (function (): string {
            app(WeeklyEvents::class)->synchronize();

            return 'synchronized';
        })(),
        'weekly-attempt' => (function () use ($input): string {
            config($input['judge_config']);
            $data = $input['data'];
            if ($input['distinct']) {
                $data['confirmation_id'] = (string) Str::uuid();
            }

            return app(ChallengeSubmissions::class)->submit(User::findOrFail($input['actor_id']), 'module-test-session', CodingChallenge::findOrFail($input['challenge_id']), $data, WeeklyEvent::findOrFail($input['event_id'])) ? 'attempted' : 'error';
        })(),
        'register' => app(RegisterAccount::class)->handle($input) ? 'created' : 'error',
        'resend' => app(EmailVerificationService::class)->request(User::where('email', $input['email'])->sole()) ? 'queued' : 'limited',
        'verify' => app(EmailVerificationService::class)->verify($input['token']) ? 'verified' : 'invalid',
        'recover-request' => app(AccountRecoveryService::class)->request(User::findOrFail($input['user_id'])) ? 'queued' : 'limited',
        'recover-complete' => app(AccountRecoveryService::class)->complete($input['proof'], $input['password']) ? 'recovered' : 'invalid',
        'learning-complete' => app(LearningProgression::class)->record(User::findOrFail($input['user_id']), $input['session_id'], 'sequences', 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]) ? 'saved' : 'error',
        'creator-review' => (function () use ($input): string {
            app(ReviewInstructorApplication::class)->handle(User::findOrFail($input['actor_id']), $input['session_id'], InstructorApplication::findOrFail($input['application_id']), 1, 'Approved', null, true);

            return 'reviewed';
        })(),
        'module-review' => (function () use ($input): string {
            app(ModulePublishing::class)->review(User::findOrFail($input['actor_id']), $input['session_id'], LearningModule::findOrFail($input['module_id']), 2, 'Approved', null);

            return 'reviewed';
        })(),
        'course-review' => (function () use ($input): string {
            app(CoursePublishing::class)->review(User::findOrFail($input['actor_id']), $input['session_id'], LearningCourse::findOrFail($input['course_id']), 2, 'Approved', null);

            return 'reviewed';
        })(),
        'course-save' => app(CoursePublishing::class)->save(User::findOrFail($input['actor_id']), $input['session_id'], $input['data'], LearningCourse::findOrFail($input['course_id'])) ? 'saved' : 'error',
        'challenge-save' => app(ChallengePublishing::class)->save(User::findOrFail($input['actor_id']), $input['session_id'], $input['data'], CodingChallenge::findOrFail($input['challenge_id'])) ? 'saved' : 'error',
        'challenge-attempt' => (function () use ($input): string {
            config($input['judge_config']);
            $data = $input['data'];
            if ($input['distinct']) {
                $data['confirmation_id'] = (string) Str::uuid();
            }

            return app(ChallengeSubmissions::class)->submit(User::findOrFail($input['actor_id']), $input['session_id'], CodingChallenge::findOrFail($input['challenge_id']), $data) ? 'attempted' : 'error';
        })(),
        'challenge-review' => (function () use ($input): string {
            app(ChallengePublishing::class)->review(User::findOrFail($input['actor_id']), $input['session_id'], CodingChallenge::findOrFail($input['challenge_id']), 2, 'Approved', null);

            return 'reviewed';
        })(),
        'course-enroll' => app(CourseLearning::class)->enroll(User::findOrFail($input['actor_id']), $input['session_id'], $input['course_id'], $input['revision_id']) ? 'enrolled' : 'error',
        'course-complete' => app(CourseLearning::class)->complete(User::findOrFail($input['actor_id']), $input['session_id'], $input['course_id'], $input['slot_id'], 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]) ? 'saved' : 'error',
        'module-save' => app(ModulePublishing::class)->save(User::findOrFail($input['actor_id']), $input['session_id'], $input['data'], LearningModule::findOrFail($input['module_id'])) ? 'saved' : 'error',
        'module-complete' => app(LearningProgression::class)->recordModule(User::findOrFail($input['actor_id']), $input['session_id'], $input['module_id'], $input['revision_id'], 'game', ['program' => ['right', 'right', 'up', 'right', 'right']]) ? 'saved' : 'error',
        'login' => strtolower(app(LoginAccount::class)->attempt($input['email'], $input['password'], $session)->name),
        'confirm' => (function () use ($input, $session): string {
            $session->put('login_confirmation', $input['confirmation']);

            return strtolower(app(LoginAccount::class)->confirm($session)->name);
        })(),
    };
    echo $result."\n";
} catch (ValidationException) {
    echo "duplicate\n";
} catch (Throwable) {
    // Test diagnostics must never print input tokens, credentials or provider details.
    echo "error\n";
    exit(1);
}
