<?php

namespace App\Services\Publishing;

use App\Enums\Role;
use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccessSettings
{
    public function rules(string $type): array
    {
        [$minimum, $maximum] = config('economy.price_bands.'.$type);

        return ['price_kb' => ['sometimes', 'integer', function (string $attribute, mixed $value, \Closure $fail) use ($minimum, $maximum): void {
            if (is_numeric($value) && (int) $value !== 0 && ((int) $value < $minimum || (int) $value > $maximum)) {
                $fail('Choose free access or '.$minimum.'–'.$maximum.' KodeBits.');
            }
        }], 'minimum_xp' => ['sometimes', 'integer', Rule::in(array_keys(config('economy.ranks')))],
            'prerequisite_modules' => ['sometimes', 'array', 'list', 'max:5'],
            'prerequisite_modules.*' => ['required', 'integer', 'min:1', 'distinct']];
    }

    public function attributes(User $owner, string $type, array $data, ?int $contentId = null): array
    {
        $fields = Validator::make($data, $this->rules($type))->validate();
        $prerequisites = array_map('intval', $fields['prerequisite_modules'] ?? []);
        $eligible = LearningModule::where('created_by', $owner->id)->whereIn('id', $prerequisites)->where('status', 'Published')
            ->whereNull('staff_withdrawn_at')->whereHas('publishedRevision', fn ($query) => $query->where('review_status', 'Approved'))
            ->when($type === 'module' && $contentId !== null, fn ($query) => $query->where('id', '<>', $contentId))
            ->orderBy('id')->lockForUpdate()->get(['id'])->count();
        if ($eligible !== count($prerequisites)) {
            throw ValidationException::withMessages(['prerequisite_modules' => 'Choose distinct owned Published modules; a module cannot require itself.']);
        }
        if ($type === 'module' && $contentId !== null && $prerequisites !== []) {
            // UNION deduplicates visited IDs, so even corrupt pre-existing cycles terminate.
            $cycle = DB::selectOne('WITH RECURSIVE dependencies(id) AS (
                SELECT unnest(?::bigint[])
                UNION SELECT prerequisite.value::bigint FROM dependencies d
                JOIN learning_modules m ON m.id = d.id
                JOIN module_revisions r ON r.id = m.published_revision_id
                CROSS JOIN LATERAL jsonb_array_elements_text(r.prerequisite_modules) prerequisite(value)
                WHERE m.created_by = ?
            ) SELECT EXISTS (SELECT 1 FROM dependencies WHERE id = ?) AS cycle',
                ['{'.implode(',', $prerequisites).'}', $owner->id, $contentId]);
            if ($cycle->cycle) {
                throw ValidationException::withMessages(['prerequisite_modules' => 'These prerequisites form a learning loop. Choose a path learners can complete in order.']);
            }
        }

        return ['price_kb' => (int) ($fields['price_kb'] ?? 0), 'minimum_xp' => (int) ($fields['minimum_xp'] ?? 0),
            'prerequisite_modules' => $prerequisites,
            'creator_settlement' => $type === 'challenge' && $owner->account_role === Role::Contributor ? 'KodeBits' : 'Cash'];
    }

    public function options(User $owner): Collection
    {
        return LearningModule::where('created_by', $owner->id)->where('status', 'Published')->whereNull('staff_withdrawn_at')
            ->with('publishedRevision:id,module_id,title')->orderByDesc('id')->limit(200)->get();
    }
}
