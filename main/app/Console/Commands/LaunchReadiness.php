<?php

namespace App\Console\Commands;

use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class LaunchReadiness extends Command
{
    protected $signature = 'kody:launch-check';

    protected $description = 'Check secret-free launch configuration without contacting external providers';

    public function handle(): int
    {
        try {
            $database = DB::connection()->getDriverName() === 'pgsql' && DB::selectOne('SELECT 1 AS ready')->ready === 1;
        } catch (Throwable) {
            $database = false;
        }
        $checks = [
            'PostgreSQL reachable' => $database,
            'HTTPS and debug disabled' => ! config('app.debug') && is_string(config('app.url')) && str_starts_with(config('app.url'), 'https://') && filter_var(config('app.url'), FILTER_VALIDATE_URL),
            'Secure HTTP-only session cookies' => config('session.secure') === true && config('session.http_only') === true && in_array(config('session.same_site'), ['lax', 'strict'], true),
            'Transactional database queue and safe retry interval' => config('queue.default') === 'database'
                && (config('queue.connections.database.connection') ?? config('database.default')) === config('database.default') && config('queue.connections.database.retry_after') > 60,
            'Named primary and backup responder' => filled(config('operations.operations_owner')) && filled(config('operations.backup_responder')),
            'Budget enforcement' => config('operations.budget_enforced') === true,
            'Staging evidence attested' => config('operations.staging_verified') === true,
            'Private credential disk' => config('account.credentials.disk') !== 'public' && config('filesystems.disks.'.config('account.credentials.disk').'.visibility', 'private') === 'private',
            'Payments disabled or activation gate complete' => ! config('xendit.enabled') || app(Readiness::class)->available(),
        ];
        $this->table(['Configuration check', 'Result'], collect($checks)->map(fn ($passed, $name) => [$name, $passed ? 'PASS' : 'MISSING'])->all());
        $this->line('Passing configuration does not prove live integrations, TLS, backups, monitoring, tax setup or measured capacity. Verify the staging runbook before release.');

        return in_array(false, array_map(fn ($value) => (bool) $value, $checks), true) ? self::FAILURE : self::SUCCESS;
    }
}
