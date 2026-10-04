<?php

namespace App\Console\Commands;

use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Console\Command;

class SetupStatus extends Command
{
    protected $signature = 'kody:setup-status';

    protected $description = 'Show secret-free local setup checks without contacting providers or enabling services';

    public function handle(): int
    {
        $google = config('services.google');
        $judge = config('judge0');
        $rows = [
            ['Google identity', $google['enabled'] ? 'Enabled; live verification required' : 'Disabled',
                $this->present($google['client_id']) && $this->present($google['client_secret']) && $this->https($google['redirect']) ? 'Config present; verify exact callback and consent' : 'Client credentials and HTTPS callback needed'],
            ['Judge0', $judge['enabled'] ? 'Enabled; provider check required' : 'Disabled',
                $this->present($judge['rapidapi_key'] ?? null) || $this->present($judge['auth_token'] ?? null) ? 'Credential present; verify compiler IDs and limits' : 'Private provider credentials needed'],
            ['SendGrid SMTP', config('account.verification.mailer') === 'sendgrid' ? 'Selected for verification' : 'Not selected for verification',
                $this->present(config('mail.mailers.sendgrid.password')) ? 'Key present; verify sender/domain and actual delivery' : 'Private SendGrid key and authenticated sender needed'],
            ['Private credentials', config('account.credentials.disk'), 'Verify private storage, staff-only downloads and backup restore'],
            ['Queue', config('queue.default'), 'Verify supervised worker, restart and failed-job alerts in staging'],
            ['Scheduler', 'Registered in Laravel', 'Verify minute trigger and task execution in staging'],
            ['Xendit economy', app(Readiness::class)->available() ? 'Configuration gate satisfied' : 'Disabled / activation gate incomplete', 'Sandbox callback/retrieval, contract fees, named responders, budget and staging evidence required'],
            ['Recovery targets', 'RPO 15m / RTO 4h; backup 35 days', 'Verify RDS PITR, private S3 version erasure, encrypted key recovery and monthly isolated restore'],
            ['Pilot budget', 'PHP12,000 / month', config('operations.budget_enforced') ? 'Current-month operator report controls new admissions' : 'Enable budget enforcement before live payment activation'],
            ['Production baseline', config('app.debug') || ! $this->https(config('app.url')) ? 'Needs HTTPS URL / debug disabled' : 'URL and debug settings present', 'Verify TLS, secure cookies and PostgreSQL CA validation'],
        ];
        $this->table(['Component', 'Local configuration', 'Next verification'], $rows);
        $this->info('Configuration presence is not live verification. No provider calls, emails, deployment or settings changes were made.');

        return self::SUCCESS;
    }

    private function present(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function https(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false && parse_url($value, PHP_URL_SCHEME) === 'https';
    }
}
