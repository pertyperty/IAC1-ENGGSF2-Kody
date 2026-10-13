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
                'overview' => [...$this->accounts($start, $end), ...$this->content($start, $end), ...$this->learning($start, $end),
                    ...$this->groups('challenge_submissions', 'submitted_at', 'status', ['Queued', 'Evaluating', 'Passed', 'Failed', 'Unavailable'], 'Attempts submitted', $start, $end),
                    ...$this->economy($start, $end), ...$this->rewards($start, $end), ...$this->engagement($start, $end), ...$this->governance($start, $end), ...$this->operations($start, $end)],
                'engagement' => $this->engagement($start, $end),
                'governance' => $this->governance($start, $end),
                'operations' => $this->operations($start, $end),
                'accounts' => $this->accounts($start, $end),
                'content' => $this->content($start, $end),
                'learning' => $this->learning($start, $end),
                'economy' => $this->economy($start, $end),
                'rewards' => $this->rewards($start, $end),
                'execution' => $this->groups('challenge_submissions', 'submitted_at', 'status',
                    ['Queued', 'Evaluating', 'Passed', 'Failed', 'Unavailable'], 'Attempts submitted', $start, $end),
                default => throw new LogicException('Unsupported system report.'),
            };

            return ['rows' => $rows, 'insights' => $this->insights($start, $end), 'generated_at' => CarbonImmutable::now('Asia/Manila'), 'filters' => $filters,
                'trends' => app(ReportTrends::class)->generate($start, $end, $filters['group'] ?? 'day'),
                'financials' => in_array($filters['type'], ['overview', 'economy'], true) ? app(ReportFinancials::class)->snapshot($start, $end) : null];
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

    private function insights(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $priorStart = $start->subSeconds($start->diffInSeconds($end));
        $registrations = $this->window('users', 'created_at', $start, $end)->count();
        $previous = $this->window('users', 'created_at', $priorStart, $start)->count();
        $active = $this->window('learning_activity_days', 'completed_at', $start, $end)->distinct()->count('user_id');
        $attempts = $this->window('challenge_submissions', 'submitted_at', $start, $end);
        $passed = (clone $attempts)->where('status', 'Passed')->count();
        $evaluated = (clone $attempts)->whereIn('status', ['Passed', 'Failed'])->count();
        $pending = (clone $attempts)->whereIn('status', ['Queued', 'Evaluating'])->count();

        return [
            ['title' => 'Account growth', 'value' => $registrations, 'detail' => $registrations.' registrations; '.$previous.' in the preceding equal-length window. Difference: '.($registrations - $previous).'.'],
            ['title' => 'Active assessed learners', 'value' => $active, 'detail' => $active.' distinct participants recorded validated game or quiz activity. Reading and browsing are excluded.'],
            ['title' => 'Coding pass rate', 'value' => $evaluated ? round($passed * 100 / $evaluated).'%' : 'No results', 'detail' => $passed.' passed out of '.$evaluated.' terminal Passed/Failed attempts submitted in this window. Pending and unavailable results are excluded.'],
            ['title' => 'Evaluation backlog', 'value' => $pending, 'detail' => $pending.' submitted attempts are currently queued or evaluating. Check worker/provider readiness if this persists.'],
        ];
    }

    private function engagement(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            ['metric' => 'Current reactions updated in window', 'count' => $this->window('content_reactions', 'updated_at', $start, $end)->count()],
            ['metric' => 'Help entries created', 'count' => $this->window('faq_entries', 'created_at', $start, $end)->count()],
            ['metric' => 'First article module completions', 'count' => $this->window('module_read_completions', 'completed_at', $start, $end)->count()],
        ];
    }

    private function governance(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            ['metric' => 'Instructor application versions submitted', 'count' => $this->window('instructor_application_versions', 'created_at', $start, $end)->count()],
            ['metric' => 'Contributor applications submitted', 'count' => $this->window('contributor_applications', 'created_at', $start, $end)->count()],
            ['metric' => 'Recorded account enforcement actions', 'count' => $this->window('account_enforcements', 'created_at', $start, $end)->count()],
            ['metric' => 'Recorded Moderator role changes', 'count' => $this->window('account_role_changes', 'created_at', $start, $end)->count()],
            ['metric' => 'Recorded content moderation actions', 'count' => $this->window('content_moderation_actions', 'created_at', $start, $end)->count()],
        ];
    }

    private function operations(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            ['metric' => 'Verification emails sent', 'count' => $this->window('verification_deliveries', 'sent_at', $start, $end)->where('purpose', 'verification')->count()],
            ['metric' => 'Financial emails sent', 'count' => $this->window('financial_deliveries', 'sent_at', $start, $end)->where('state', 'Sent')->count()],
            ['metric' => 'Background job failures recorded', 'count' => $this->window('failed_jobs', 'failed_at', $start, $end)->count()],
        ];
    }
}
