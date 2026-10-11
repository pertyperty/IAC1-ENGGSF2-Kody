<?php

namespace Database\Seeders;

use App\Enums\Role;

class LocalLearnerAccountsSeeder extends LocalRoleAccountsSeeder
{
    protected function accounts(): array
    {
        return [
            ['learner2@kody.local', 'kody_learner_two', Role::Learner],
            ['learner3@kody.local', 'kody_learner_three', Role::Learner],
            ['learner4@kody.local', 'kody_learner_four', Role::Learner],
            ['learner5@kody.local', 'kody_learner_five', Role::Learner],
        ];
    }
}
