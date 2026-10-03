<?php

namespace App\Services\Challenges\Judge0;

use App\Models\CodingChallengeRevision;
use Illuminate\Support\Facades\DB;

class ProviderReadiness
{
    public function verify(): void
    {
        $client = app(Judge0Client::class);
        $fingerprint = $client->fingerprint();
        $languages = $client->languages();
        $selected = [];
        foreach (config('judge0.languages') as $key => $id) {
            $entry = collect($languages)->first(fn ($language) => is_array($language) && ($language['id'] ?? null) === $id);
            $pattern = match ($key) {
                'python' => '/^Python \(/', 'java' => '/^Java \(/', 'cpp' => '/^C\+\+ \(/'
            };
            if (! is_string($entry['name'] ?? null) || ! preg_match($pattern, $entry['name'])) {
                throw new ProviderUnavailable;
            }
            $selected[$key] = ['id' => $id, 'name' => $entry['name']];
        }
        $limits = $client->limits();
        foreach (['max_cpu_time_limit', 'max_memory_limit', 'max_wall_time_limit', 'max_cpu_extra_time',
            'max_stack_limit', 'max_max_file_size', 'max_max_processes_and_or_threads', 'max_number_of_runs'] as $key) {
            if (! is_numeric($limits[$key] ?? null) || $limits[$key] <= 0) {
                throw new ProviderUnavailable;
            }
        }
        if ($limits['max_wall_time_limit'] < 15 || $limits['max_cpu_extra_time'] < 0.5 || $limits['max_stack_limit'] < 64000
            || $limits['max_max_file_size'] < 1024 || $limits['max_max_processes_and_or_threads'] < 60
            || ! is_bool($limits['enable_network'] ?? null)
            || ($limits['enable_network'] && ($limits['allow_enable_network'] ?? false) !== true)) {
            throw new ProviderUnavailable;
        }
        DB::table('judge0_profiles')->updateOrInsert(['fingerprint' => $fingerprint], [
            'languages' => json_encode($selected, JSON_THROW_ON_ERROR), 'limits' => json_encode($limits, JSON_THROW_ON_ERROR), 'verified_at' => now()]);
    }

    public function profile(CodingChallengeRevision $revision): ?object
    {
        $client = app(Judge0Client::class);
        if (! $client->configured()) {
            return null;
        }
        $profile = DB::table('judge0_profiles')->where('fingerprint', $client->fingerprint())->first();
        $limits = $profile === null ? [] : json_decode($profile->limits, true);

        return $profile !== null && $revision->cpu_time_ms / 1000 <= ($limits['max_cpu_time_limit'] ?? 0)
            && $revision->memory_kib <= ($limits['max_memory_limit'] ?? 0) ? $profile : null;
    }
}
