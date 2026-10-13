<?php

namespace Database\Seeders;

/** Compatibility entry point; Learner fixtures are now included in LocalRoleAccountsSeeder. */
class LocalLearnerAccountsSeeder extends LocalRoleAccountsSeeder
{
    protected function accounts(): array
    {
        return [];
    }
}
