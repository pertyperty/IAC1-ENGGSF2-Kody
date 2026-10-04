<?php

use App\Services\Transactions\FinancialRecovery;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command('kody:submissions-expire')->everyMinute()->withoutOverlapping();
Schedule::command('kody:weekly-events-sync')->everyMinute()->withoutOverlapping();
Schedule::command('kody:account-erasures-retry')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('kody:google-attempts-prune')->daily()->withoutOverlapping();
Schedule::command('kody:finance-recover')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('kody:finance-recover', function (): void {
    app(FinancialRecovery::class)->sweep();
    $this->info('Recovered pending financial work; uncertain requests require review.');
})->purpose('Recover durable financial work without repeating uncertain provider POSTs or emails');

Artisan::command('kody:google-attempts-prune', function (): void {
    $count = DB::table('google_auth_attempts')->where('expires_at', '<', now()->subDay())->delete();
    $this->info("Pruned {$count} expired Google authentication attempts.");
})->purpose('Remove short-lived OAuth callback records after their retention window');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
