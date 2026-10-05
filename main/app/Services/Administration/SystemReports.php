<?php

namespace App\Services\Administration;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class SystemReports
{
    public function generate(User $actor, string $sessionId, array $filters): array
    {
        // The report owns its read-only snapshot, rather than nesting in a write transaction.
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('System reports require an independent read-only transaction.');
        }

        return DB::transaction(function () use ($actor, $sessionId, $filters): array {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');
            $admin = User::findOrFail($actor->id);
            app(CurrentAccountSession::class)->assert($admin, $sessionId);
            Gate::forUser($admin)->authorize('viewReports', User::class);
            $start = CarbonImmutable::parse($filters['from'], 'Asia/Manila')->startOfDay()->utc();
            $end = CarbonImmutable::parse($filters['to'], 'Asia/Manila')->addDay()->startOfDay()->utc();
            $rows = match ($filters['type']) {
                'accounts' => $this->accounts($start, $end),
                'content' => $this->content($start, $end),
                'learning' => $this->learning($start, $end),
                'economy' => $this->economy($start, $end),
                'rewards' => $this->rewards($start, $end),
                'execution' => $this->groups('challenge_submissions', 'submitted_at', 'status',
                    ['Queued', 'Evaluating', 'Passed', 'Failed', 'Unavailable'], 'Attempts submitted', $start, $end),
                default => throw new LogicException('Unsupported system report.'),
            };

            return ['rows' => $rows, 'generated_at' => CarbonImmutable::now('Asia/Manila'), 'filters' => $filters];
        });
    }

    private function accounts(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [...$this->groups('users', 'created_at', 'account_status', array_column(AccountStatus::cases(), 'value'), 'Registered accounts by current status', $start, $end),
            ...$this->groups('users', 'created_at', 'account_role', array_column(Role::cases(), 'value'), 'Registered accounts by current role', $start, $end)];
    }

    private function content(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = [];
        foreach (['learning_modules' => 'Modules created', 'learning_courses' => 'Courses created', 'coding_challenges' => 'Challenges created'] as $table => $label) {
            $rows = [...$rows, ...$this->groups($table, 'created_at', 'status', ['Draft', 'Published', 'Archived', 'Deleted'], $label, $start, $end)];
        }

        return $rows;
    }

    private function learning(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            ['metric' => 'Recorded validated game completions', 'count' => $this->window('learning_activity_days', 'completed_at', $start, $end)->where('kind', 'game')->count()],
            ['metric' => 'Recorded validated quiz completions', 'count' => $this->window('learning_activity_days', 'completed_at', $start, $end)->where('kind', 'quiz')->count()],
            ['metric' => 'Course enrollments', 'count' => $this->window('course_enrollments', 'enrolled_at', $start, $end)->count()],
            ['metric' => 'Completed course assignments', 'count' => $this->window('course_module_progress', 'completed_at', $start, $end)->count()],
        ];
    }

    private function groups(string $table, string $time, string $group, array $values, string $label, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $counts = $this->window($table, $time, $start, $end)->select($group)->selectRaw('COUNT(*) AS total')->groupBy($group)->pluck('total', $group);

        return array_map(fn (string $value): array => ['metric' => $label.' · '.$value, 'count' => (int) ($counts[$value] ?? 0)], $values);
    }

    private function economy(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $operations = $this->window('wallet_operations', 'created_at', $start, $end);

        return [
            ['metric' => 'Purchased wallet credits · KB', 'count' => (int) (clone $operations)->where('kind', 'Purchase')->sum('kodebits')],
            ['metric' => 'Paid content debits · KB', 'count' => -(int) (clone $operations)->where('kind', 'Access')->sum('kodebits')],
            ['metric' => 'Purchased wallet backing · PHP centavos', 'count' => (int) (clone $operations)->where('kind', 'Purchase')->sum('value_minor')],
            ['metric' => 'Net platform cash postings · PHP centavos', 'count' => (int) $this->window('platform_cash_entries', 'created_at', $start, $end)->sum('amount_minor')],
        ];
    }

    private function rewards(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            ['metric' => 'Validated XP grants · XP', 'count' => (int) $this->window('xp_awards', 'created_at', $start, $end)->sum('xp')],
            ['metric' => 'Unique XP grants · records', 'count' => $this->window('xp_awards', 'created_at', $start, $end)->count()],
            ['metric' => 'Funded weekly prizes · KB', 'count' => (int) $this->window('wallet_operations', 'created_at', $start, $end)->where('kind', 'Reward')->sum('kodebits')],
            ['metric' => 'Published weekly result sets · events', 'count' => $this->window('weekly_result_sets', 'published_at', $start, $end)->where('status', 'Published')->count()],
        ];
    }

    private function window(string $table, string $time, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        // Identifiers come only from the fixed report definitions above; values are bound.
        return DB::table($table)->where($time, '>=', $start)->where($time, '<', $end);
    }
}
