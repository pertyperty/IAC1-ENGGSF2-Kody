<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\PrivateFileRemoval;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\FaqManagement;
use App\Services\Challenges\ChallengePublishing;
use App\Services\Content\CoursePublishing;
use App\Services\Content\CreatorExamples;
use App\Services\Content\ModulePublishing;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Removable local fixture. Uses publishing/review services; never seeds learner progress or money. */
class LocalShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $connection = DB::connection();
        if (! app()->environment(['local', 'testing']) || $connection->getDriverName() !== 'pgsql'
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('Showcase fixtures require local/testing and loopback PostgreSQL.');
        }
        $uploaded = [];
        try {
            DB::transaction(function () use (&$uploaded): void {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('kody_local_showcase_seed'))");
                $users = User::whereIn('email', ['instructor@kody.local', 'contributor@kody.local', 'moderator@kody.local', 'admin2@kody.local'])
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('email');
                $roles = ['instructor@kody.local' => Role::Instructor, 'contributor@kody.local' => Role::Contributor,
                    'moderator@kody.local' => Role::Moderator, 'admin2@kody.local' => Role::Administrator];
                $original = [];
                $session = (string) Str::uuid();
                foreach ($roles as $email => $role) {
                    $user = $users->get($email);
                    if ($user === null || $user->account_role !== $role || $user->account_status !== AccountStatus::Active || $user->email_verified_at === null) {
                        throw new RuntimeException('Showcase needs the verified Active local role fixtures; existing accounts are never promoted or reactivated.');
                    }
                    // Uncommitted scoped authoring session. Restore the exact browser session before commit.
                    $original[$user->id] = $user->only(['active_session_hash', 'active_session_expires_at']);
                    DB::table('users')->where('id', $user->id)->update(['active_session_hash' => hash('sha256', $session), 'active_session_expires_at' => now()->addHour()]);
                }
                $author = $users['instructor@kody.local'];
                $reviewer = $users['moderator@kody.local'];
                $modules = app(ModulePublishing::class);
                $courses = app(CoursePublishing::class);
                foreach (config('curriculum') as $slug => $plan) {
                    $ids = [];
                    foreach ($plan['lessons'] as $example) {
                        $key = 'module:'.$example;
                        $id = $this->existing($key);
                        if ($id === null) {
                            $fields = app(CreatorExamples::class)->fields($example);
                            if (($fields['assessment_kind'] ?? '') === 'none') {
                                $fields['attachments'] = array_map(fn ($extension) => new UploadedFile(database_path('fixtures/lesson-resources/programming-foundations.'.$extension), 'programming-foundations.'.$extension, null, null, true), ['pdf', 'docx', 'pptx']);
                                $fields['video_url'] = 'https://www.youtube.com/watch?v=JP7ITIXGpHk';
                                $fields['content'] .= "\n\nOptional further learning: CS50P Lecture 0, Functions and Variables, by David J. Malan / Harvard CS50 (2022). The embedded video is hosted by CS50. These Kody notes and exercises are original; no third-party course files are redistributed.";
                            }
                            $module = $modules->save($author, $session, $fields);
                            foreach ($module->latestRevision->attachments ?? [] as $asset) {
                                $uploaded[] = [$author->id, $asset];
                            }
                            $modules->submit($author, $session, $module, 1);
                            $modules->review($reviewer, $session, $module->fresh(), 2, 'Approved', 'Local showcase reviewed fixture.');
                            $id = $module->id;
                            $this->mark($key, 'learning_module', $id);
                        }
                        $ids[] = $id;
                    }
                    if ($this->existing('course:'.$slug) === null) {
                        $course = $courses->save($author, $session, $plan + ['module_ids' => $ids, 'sequential' => true]);
                        $courses->submit($author, $session, $course, 1);
                        $courses->review($reviewer, $session, $course->fresh(), 2, 'Approved', 'Local showcase reviewed fixture.');
                        $this->mark('course:'.$slug, 'learning_course', $course->id);
                    }
                }
                $challenges = app(ChallengePublishing::class);
                foreach (config('challenge-examples') as $slug => $example) {
                    foreach (['python', 'java', 'cpp'] as $language) {
                        $key = 'challenge:'.$slug.':'.$language;
                        if ($this->existing($key) !== null) {
                            continue;
                        }
                        $challenge = $challenges->save($users['contributor@kody.local'], $session, array_replace($example,
                            ['title' => $example['title'].' · '.strtoupper($language), 'language' => $language, 'difficulty' => 'Easy', 'cpu_time_ms' => 1000, 'memory_kib' => 262144]));
                        $challenges->submit($users['contributor@kody.local'], $session, $challenge, 1);
                        $challenges->review($reviewer, $session, $challenge->fresh(), 2, 'Approved', 'Local showcase reviewed fixture.');
                        $this->mark($key, 'coding_challenge', $challenge->id);
                    }
                }
                foreach (config('showcase-help') as $key => $entry) {
                    if ($this->existing('faq:'.$key) === null) {
                        $faq = app(FaqManagement::class)->save($users['admin2@kody.local'], $session, $entry + ['record_version' => 1]);
                        $this->mark('faq:'.$key, 'faq_entry', $faq->id);
                    }
                }
                foreach ($original as $id => $fields) {
                    DB::table('users')->where('id', $id)->update($fields);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($uploaded as [$ownerId, $asset]) {
                DB::transaction(fn () => app(PrivateFileRemoval::class)->queue($ownerId, $asset['disk'], $asset['path']));
            }
            if ($exception instanceof QueryException) {
                throw new RuntimeException('Local showcase storage failed. No content was changed.');
            }
            throw $exception;
        }
        $this->command?->info('Local showcase ready. Existing fixture content, accounts, sessions and learning history were preserved.');
    }

    private function existing(string $key): ?int
    {
        $id = DB::table('audit_events')->where('event', 'local.showcase-created')->where('context->fixture_key', $key)->value('subject_id');

        return $id === null ? null : (int) $id;
    }

    private function mark(string $key, string $type, int $id): void
    {
        app(AuditRecorder::class)->record(null, null, 'local.showcase-created', $type, (string) $id, ['fixture_key' => $key]);
    }
}
