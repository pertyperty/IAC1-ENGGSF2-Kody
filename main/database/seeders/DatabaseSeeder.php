<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Never provision development identities through production/staging seeds.
        if (app()->environment('local')) {
            $this->call(LocalRoleAccountsSeeder::class);
        }
    }
}
