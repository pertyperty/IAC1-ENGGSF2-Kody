<?php

namespace App\Services\Administration;

use App\Models\FaqEntry;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FaqManagement
{
    public function rules(): array
    {
        return ['question' => ['required', 'string', 'max:255'], 'answer' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::in(array_keys(config('help.categories')))], 'record_version' => ['required', 'integer', 'min:1'],
            'status' => ['prohibited'], 'created_by' => ['prohibited']];
    }

    public function save(User $actor, string $sessionId, array $data, ?FaqEntry $entry = null): FaqEntry
    {
        foreach (['question', 'answer'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        $data = Validator::make($data, $this->rules())->validate();
        try {
            return DB::transaction(function () use ($actor, $sessionId, $data, $entry): FaqEntry {
                $user = $this->account($actor, $sessionId);
                Gate::forUser($user)->authorize('create', FaqEntry::class);
                $fields = ['question' => trim($data['question']), 'answer' => trim($data['answer']), 'category' => $data['category']];
                if ($entry === null) {
                    $current = FaqEntry::create($fields + ['created_by' => $user->id]);
                } else {
                    $current = FaqEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
                    $this->version($current, (int) $data['record_version']);
                    Gate::forUser($user)->authorize('update', $current);
                    $current->update($fields + ['record_version' => $current->record_version + 1]);
                }
                app(AuditRecorder::class)->record($user->id, null, $entry === null ? 'faq.created' : 'faq.updated', 'faq_entry', (string) $current->id,
                    ['version' => $current->record_version, 'category' => $current->category]);

                return $current;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505' && str_contains($exception->getMessage(), 'faq_question_unique')) {
                throw ValidationException::withMessages(['question' => 'Another FAQ already asks this question. Edit that entry or use a different question.']);
            }
            throw $exception;
        }
    }

    public function delete(User $actor, string $sessionId, FaqEntry $entry, int $version, bool $confirmed): void
    {
        DB::transaction(function () use ($actor, $sessionId, $entry, $version, $confirmed): void {
            $user = $this->account($actor, $sessionId);
            Gate::forUser($user)->authorize('create', FaqEntry::class);
            $current = FaqEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $this->version($current, $version);
            Gate::forUser($user)->authorize('update', $current);
            if (! $confirmed) {
                throw ValidationException::withMessages(['confirmed' => 'Confirm FAQ deletion.']);
            }
            $current->update(['status' => 'Deleted', 'record_version' => $current->record_version + 1]);
            app(AuditRecorder::class)->record($user->id, null, 'faq.deleted', 'faq_entry', (string) $current->id, ['version' => $current->record_version]);
        });
    }

    private function account(User $actor, string $sessionId): User
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        app(CurrentAccountSession::class)->assert($user, $sessionId);

        return $user;
    }

    private function version(FaqEntry $entry, int $version): void
    {
        if ($entry->record_version !== $version) {
            throw ValidationException::withMessages(['record_version' => 'This FAQ changed. Reload before saving or deleting.']);
        }
    }
}
