<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Account\ContributorEligibility;
use Database\Seeders\LocalRoleApplicationDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Throwable;

class PrepareLocalRoleDemo extends Command
{
    protected $signature = 'kody:role-demo-prepare {--reinstate-five : Explicitly reinstate suspended Learner 5 through audited moderation}';

    protected $description = 'Prepare local Learners 4 and 5 for manual role-application demonstrations, without approving them';

    public function handle(LocalRoleApplicationDemoSeeder $fixtures): int
    {
        try {
            $fixtures->prepare((bool) $this->option('reinstate-five'));
            foreach (User::whereIn('email', ['learner4@kody.local', 'learner5@kody.local'])->get() as $user) {
                $progress = app(ContributorEligibility::class)->snapshot($user);
                $this->line($user->username.' · '.$user->account_role->name.' · '.$progress['account_age_days'].' days · '.$progress['completed_modules_count'].' assessed modules · '.$progress['completed_challenges_count'].' passed demo quests');
            }
            $this->info('No application was submitted or approved. Use Profile & security to apply, then review from a separate staff account.');
            $this->line('Sample supporting document: '.database_path('fixtures/lesson-resources/programming-foundations.pdf').' (demo only; not a real credential).');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception instanceof QueryException ? 'Demo storage failed; no fixture changes were committed.' : $exception->getMessage());

            return self::FAILURE;
        }
    }
}
