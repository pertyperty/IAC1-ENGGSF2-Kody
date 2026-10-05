<?php

namespace App\Services\Publishing;

use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Administration\AuditRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

abstract class OwnedContentDeletion
{
    abstract protected function modelClass(string $kind): string;

    abstract protected function specificDependencies(string $kind, Model $content): array;

    abstract protected function removeDefinition(string $kind, Model $content): void;

    public function confirmation(User $actor, string $sessionId, string $kind, int $id): array
    {
        return DB::transaction(function () use ($actor, $sessionId, $kind, $id): array {
            [, $content] = $this->locked($actor, $sessionId, $kind, $id);
            $content->load('latestRevision');

            return ['content' => $content, 'kind' => $kind, 'reasons' => $this->dependencies($kind, $content)];
        });
    }

    public function delete(User $actor, string $sessionId, string $kind, int $id, int $version, bool $confirmed): void
    {
        try {
            DB::transaction(function () use ($actor, $sessionId, $kind, $id, $version, $confirmed): void {
                [$user, $content] = $this->locked($actor, $sessionId, $kind, $id);
                if (! $confirmed) {
                    throw ValidationException::withMessages(['confirmed' => 'Confirm permanent deletion.']);
                }
                if ($content->record_version !== $version) {
                    throw ValidationException::withMessages(['record_version' => 'This content changed. Reload before deleting.']);
                }
                if ($this->dependencies($kind, $content) !== []) {
                    throw ValidationException::withMessages(['content' => 'Retained dependencies prevent deletion. Archive published content instead.']);
                }
                $status = $content->status;
                // Break only this definition's publication cycle inside the locked transaction.
                $content->forceFill(['status' => 'Draft', 'published_revision_id' => null])->save();
                $this->removeDefinition($kind, $content);
                DB::table('notifications')->where('type', $kind.'.reviewed')->where('data->'.$kind.'_id', $id)->delete();
                $content->delete();
                app(AuditRecorder::class)->record($user->id, $user->id, $kind.'.deleted',
                    $kind === 'challenge' ? 'coding_challenge' : 'learning_'.$kind, (string) $id,
                    ['version' => $version, 'prior_status' => $status]);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23503') {
                throw ValidationException::withMessages(['content' => 'Retained dependencies prevent deletion. Reload to review your options.']);
            }
            // SQL bindings can contain private author content; log only the failure class.
            Log::error('Content deletion storage failed.', ['kind' => $kind, 'sqlstate' => $exception->getCode()]);
            throw new \RuntimeException('Content storage is temporarily unavailable.');
        }
    }

    private function locked(User $actor, string $sessionId, string $kind, int $id): array
    {
        $class = $this->modelClass($kind);
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);
        $content = $class::whereKey($id)->lockForUpdate()->firstOrFail();
        Gate::forUser($user)->authorize('delete', $content);

        return [$user, $content];
    }

    private function dependencies(string $kind, Model $content): array
    {
        $column = $kind.'_id';
        $checks = [
            'Retained access grants' => DB::table('content_entitlements')->where('content_type', $kind)->where('content_id', $content->id)->exists(),
            'Retained financial history' => DB::table('content_purchases')->where('content_type', $kind)->where('content_id', $content->id)->exists(),
            'Staff moderation history' => $content->isWithdrawn() || DB::table('content_moderation_actions')->where($column, $content->id)->exists(),
            'Learner openings' => DB::table('content_accesses')->where($column, $content->id)->exists(),
            'Learner feedback history' => DB::table('content_reactions')->where($column, $content->id)->exists(),
        ] + $this->specificDependencies($kind, $content);
        if ($kind === 'module') {
            foreach (['module_revisions', 'course_revisions', 'coding_challenge_revisions'] as $table) {
                $checks['Retained prerequisite references'] = ($checks['Retained prerequisite references'] ?? false)
                    || DB::table($table)->whereJsonContains('prerequisite_modules', $content->id)->exists();
            }
        }

        return array_keys(array_filter($checks));
    }
}
