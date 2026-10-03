<?php

namespace App\Console\Commands;

use App\Services\Challenges\Judge0\ProviderReadiness;
use App\Services\Challenges\Judge0\ProviderUnavailable;
use Illuminate\Console\Command;

class VerifyJudge0 extends Command
{
    protected $signature = 'kody:judge0-check';

    protected $description = 'Verify configured Judge0 compiler mappings and execution limits without executing code';

    public function handle(ProviderReadiness $readiness): int
    {
        try {
            $readiness->verify();
        } catch (ProviderUnavailable) {
            $this->error('Judge0 verification failed. Check the private provider configuration and its supported limits.');

            return self::FAILURE;
        }
        $this->info('Judge0 compiler mappings and limits verified. Configure the code-execution worker before enabling participation.');

        return self::SUCCESS;
    }
}
