<?php

use App\Actions\Account\LoginAccount;
use App\Actions\Account\RegisterAccount;
use App\Actions\Account\ReviewInstructorApplication;
use App\Models\CodingChallenge;
use App\Models\InstructorApplication;
use App\Models\LearningCourse;
use App\Models\LearningModule;
use App\Models\User;
use App\Models\WeeklyEvent;
use App\Services\Account\AccountArchival;
use App\Services\Account\AccountRecoveryService;
use App\Services\Account\EmailVerificationService;
use App\Services\Account\InstructorApplications;
use App\Services\Account\ProfileEditing;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Challenges\ChallengeSubmissions;
use App\Services\Content\CourseLearning;
use App\Services\Content\CoursePublishing;
use App\Services\Content\ModulePublishing;
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
