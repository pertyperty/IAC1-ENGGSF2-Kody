<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\ChallengeSubmission;
use App\Models\CodingChallenge;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\Administration\AccountEnforcement;
use App\Services\Administration\AuditRecorder;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Content\CreatorExamples;
use App\Services\Content\ModulePublishing;
use App\Services\Games\GameAssessment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Explicit opt-in local history for demonstrating A09/A10, never genuine earned progress. */
class LocalRoleApplicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->prepare();
    }

    public function prepare(bool $reinstateFive = false): void
    {
        $connection = DB::connection();
        if (! app()->environment(['local', 'testing']) || $connection->getDriverName() !== 'pgsql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Role demonstrations require local/testing and loopback PostgreSQL.');
        }
        DB::transaction(function () use ($reinstateFive): void {
            DB::select("SELECT pg_advisory_xact_lock(hashtext('kody_local_role_seed'))");
            $users = User::whereIn('email', ['learner4@kody.local', 'learner5@kody.local', 'instructor@kody.local', 'contributor@kody.local', 'moderator@kody.local'])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('email');
            $original = [];
            $session = (string) Str::uuid();
            foreach (['instructor@kody.local' => Role::Instructor, 'contributor@kody.local' => Role::Contributor, 'moderator@kody.local' => Role::Moderator] as $email => $role) {
                $user = $users->get($email);
                $this->fixtureAccount($user);
                if ($user->account_role !== $role || $user->account_status !== AccountStatus::Active) {
                    throw new RuntimeException('Active local creator/reviewer fixtures are required; no existing role is overwritten.');
                }
                $original[$user->id] = $user->only(['active_session_hash', 'active_session_expires_at']);
                DB::table('users')->where('id', $user->id)->update(['active_session_hash' => hash('sha256', $session), 'active_session_expires_at' => now()->addHour()]);
            }
            $learners = [];
            foreach (['learner4@kody.local', 'learner5@kody.local'] as $email) {
                $user = $users->get($email);
                $this->fixtureAccount($user);
                if ($user->account_role !== Role::Learner) {
                    // Preserve real demo decisions; rerunning never downgrades an approved applicant.
                    $approved = match ($user->account_role) {
                        Role::Instructor => DB::table('instructor_applications')->where('user_id', $user->id)->where('verification_status', 'Approved')->exists(),
                        Role::Contributor => DB::table('contributor_applications')->where('user_id', $user->id)->where('approval_status', 'Approved')->exists(),
                        default => false,
                    };
                    if (! $approved) {
                        throw new RuntimeException('The demo identity has an unrelated role; no account changed.');
                    }

                    continue;
                }
                if ($user->account_status === AccountStatus::Suspended && $email === 'learner5@kody.local' && $reinstateFive) {
                    app(AccountEnforcement::class)->change($users['moderator@kody.local'], $session, $user,
                        ['action' => 'Reinstated', 'profile_version' => $user->profile_version, 'confirmed' => true]);
                    $user->refresh();
                }
                if ($user->account_status !== AccountStatus::Active) {
                    throw new RuntimeException('The demo Learner is inactive. Explicit audited reinstatement is required.');
                }
                $before = $user->created_at->toIso8601String();
                if ($user->created_at->isAfter(now()->subDays(31))) {
                    $user->forceFill(['created_at' => now()->subDays(31)])->save();
                }
                $learners[] = $user;
                if (! DB::table('audit_events')->where('event', 'local.role-demo-prepared')->where('subject_id', (string) $user->id)->exists()) {
                    app(AuditRecorder::class)->record(null, $user->id, 'local.role-demo-prepared', 'User', (string) $user->id,
                        ['synthetic' => true, 'previous_created_at' => $before, 'demo_created_at' => $user->created_at->toIso8601String(), 'modules' => 25, 'quests' => 50]);
                }
            }
            if ($learners !== []) {
                $historyDate = now()->subDay();
                $program = ['program' => ['right', 'right', 'up', 'right', 'right']];
                for ($index = 1; $index <= 25; $index++) {
                    $module = $this->module($index, $users['instructor@kody.local'], $users['moderator@kody.local'], $session);
                    $revision = $module->publishedRevision;
                    if (! app(GameAssessment::class)->succeeds($revision->assessment, $program)) {
                        throw new RuntimeException('The demo assessment changed; no history was added.');
                    }
                    foreach ($learners as $learner) {
                        if (! DB::table('learning_activity_days')->where('user_id', $learner->id)->where('level', 'module-'.$module->id)->exists()) {
                            DB::table('learning_activity_days')->insert(['id' => (string) Str::uuid(), 'user_id' => $learner->id, 'level' => 'module-'.$module->id,
                                'kind' => 'game', 'template_version' => $revision->assessment['version'], 'business_date' => $historyDate->copy()->setTimezone('Asia/Manila')->toDateString(),
                                'validated_input' => json_encode($program + ['revision_id' => $revision->id, 'local_role_demo' => true], JSON_THROW_ON_ERROR), 'completed_at' => $historyDate]);
                        }
                    }
                }
                // Deliberately cannot match the provider's configuration fingerprint or enable execution.
                $fingerprint = hash('sha256', 'Kody local role demonstration: NOT a verified Judge0 profile');
                DB::table('judge0_profiles')->insertOrIgnore(['fingerprint' => $fingerprint, 'languages' => '{"local_role_demo":true}', 'limits' => '{"synthetic":true}', 'verified_at' => $historyDate]);
                $profile = DB::table('judge0_profiles')->where('fingerprint', $fingerprint)->value('id');
                for ($index = 1; $index <= 50; $index++) {
                    $challenge = $this->challenge($index, $users['contributor@kody.local'], $users['moderator@kody.local'], $session);
                    foreach ($learners as $learner) {
                        $participation = DB::table('challenge_participations')->where('user_id', $learner->id)->where('challenge_id', $challenge->id)->whereNull('weekly_event_id')->first();
                        if ($participation !== null) {
                            continue;
                        } // Preserve every existing attempt and outcome.
                        $id = DB::table('challenge_participations')->insertGetId(['user_id' => $learner->id, 'challenge_id' => $challenge->id, 'attempts' => 1, 'created_at' => $historyDate, 'updated_at' => $historyDate]);
                        $count = $challenge->publishedRevision->testCases()->count();
                        $submission = new ChallengeSubmission(['participation_id' => $id, 'challenge_id' => $challenge->id, 'revision_id' => $challenge->published_revision_id,
                            'provider_profile_id' => $profile, 'attempt' => 1, 'confirmation_id' => (string) Str::uuid(), 'request_digest' => hash('sha256', 'local-role-demo:'.$learner->id.':'.$challenge->id),
                            'source_code' => "# LOCAL ROLE DEMO: synthetic result; not executed by Judge0.\nprint(sum(map(int, input().split())))\n", 'language' => 'python',
                            'status' => 'Passed', 'passed_cases' => $count, 'total_cases' => $count, 'feedback' => 'LOCAL ROLE DEMO — synthetic passed result; no provider execution or rewards.',
                            'submitted_at' => $historyDate, 'completed_at' => $historyDate]);
                        $submission->id = (string) Str::uuid();
                        $submission->save();
                    }
                }
            }
            foreach ($original as $id => $fields) {
                DB::table('users')->where('id', $id)->update($fields);
            }
        });
        $this->command?->info('Role demo prepared. Accounts remain unapproved; passwords, existing history and creator sessions were preserved.');
    }

    private function fixtureAccount(?User $user): void
    {
        if ($user === null || $user->email_verified_at === null || ! DB::table('audit_events')->where('event', 'local_role_account_created')->where('subject_id', (string) $user->id)->exists()) {
            throw new RuntimeException('Use the audited local role fixtures first. No unrelated account can receive demo history.');
        }
    }

    private function module(int $index, User $author, User $reviewer, string $session): LearningModule
    {
        $key = 'module:'.$index;
        $existing = $this->existing($key);
        if ($existing !== null) {
            return LearningModule::where('created_by', $author->id)->where('status', 'Archived')->findOrFail($existing);
        }
        $service = app(ModulePublishing::class);
        $fields = app(CreatorExamples::class)->fields('sequences');
        $fields['title'] = 'Local role demo · Module '.$index;
        $fields['content'] = 'Local role application demonstration fixture. This is not a learner-authored or earned completion.';
        $module = $service->save($author, $session, $fields);
        $service->submit($author, $session, $module, 1);
        $service->review($reviewer, $session, $module->fresh(), 2, 'Approved', 'Local role demo fixture.');
        $module->refresh();
        $service->archive($author, $session, $module, $module->record_version);
        $this->mark($key, 'learning_module', $module->id);
        // Suppress only the notice generated for this newly created demo artifact.
        $author->notifications()->where('type', 'module.reviewed')->whereRaw("data::jsonb->>'module_id' = ?", [(string) $module->id])->delete();

        return $module->fresh();
    }

    private function challenge(int $index, User $author, User $reviewer, string $session): CodingChallenge
    {
        $key = 'challenge:'.$index;
        $existing = $this->existing($key);
        if ($existing !== null) {
            return CodingChallenge::where('created_by', $author->id)->where('status', 'Archived')->findOrFail($existing);
        }
        $service = app(ChallengePublishing::class);
        $fields = config('challenge-examples.sum-two') + ['language' => 'python', 'difficulty' => 'Easy', 'cpu_time_ms' => 1000, 'memory_kib' => 262144];
        $fields['title'] = 'Local role demo · Quest '.$index;
        $challenge = $service->save($author, $session, $fields);
        $service->submit($author, $session, $challenge, 1);
        $service->review($reviewer, $session, $challenge->fresh(), 2, 'Approved', 'Local role demo fixture.');
        $challenge->refresh();
        $service->archive($author, $session, $challenge, $challenge->record_version);
        $this->mark($key, 'coding_challenge', $challenge->id);
        $author->notifications()->where('type', 'challenge.reviewed')->whereRaw("data::jsonb->>'challenge_id' = ?", [(string) $challenge->id])->delete();

        return $challenge->fresh();
    }

    private function existing(string $key): ?int
    {
        $id = DB::table('audit_events')->where('event', 'local.role-demo-content')->where('context->fixture_key', $key)->value('subject_id');

        return $id === null ? null : (int) $id;
    }

    private function mark(string $key, string $type, int $id): void
    {
        app(AuditRecorder::class)->record(null, null, 'local.role-demo-content', $type, (string) $id, ['fixture_key' => $key, 'synthetic' => true]);
    }
}
