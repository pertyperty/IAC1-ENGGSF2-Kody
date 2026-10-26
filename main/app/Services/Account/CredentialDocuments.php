<?php

namespace App\Services\Account;

use App\Models\ContributorApplication;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CredentialDocuments
{
    public function available(InstructorApplication|ContributorApplication $application): bool
    {
        return $this->fileExists($application->credential_disk, $application->credential_path, $this->kind($application));
    }

    public function download(User $actor, InstructorApplication|ContributorApplication $application, ?int $version = null): StreamedResponse
    {
        Gate::forUser($actor)->authorize('view', $application);
        $kind = $this->kind($application);
        $document = $application;
        if ($version !== null) {
            abort_unless($application instanceof InstructorApplication, 404);
            $document = DB::table('instructor_application_versions')->where('instructor_application_id', $application->id)
                ->where('application_version', $version)->first();
            abort_if($document === null, 404);
        }
        $disk = $document->credential_disk;
        $path = $document->credential_path;
        abort_unless($this->fileExists($disk, $path, $kind), 404);

        app(AuditRecorder::class)->record($actor->id, $application->user_id, $kind.'_application.credential_downloaded',
            $kind.'_application', (string) $application->id, $version === null ? [] : ['application_version' => $version]);

        return Storage::disk($disk)->download($path, $kind.'-credential'.($version === null ? '' : '-v'.$version).'.'.pathinfo($path, PATHINFO_EXTENSION),
            ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    private function kind(InstructorApplication|ContributorApplication $application): string
    {
        return $application instanceof InstructorApplication ? 'instructor' : 'contributor';
    }

    private function fileExists(string $disk, string $path, string $kind): bool
    {
        $configuration = config('filesystems.disks')[$disk] ?? null;

        // Reject invalid references before resolving a disk or making storage requests.
        return is_array($configuration) && isset($configuration['driver']) && $disk !== 'public'
            && ($configuration['visibility'] ?? null) !== 'public'
            && str_starts_with($path, $kind.'-credentials/') && ! str_contains($path, '..') && ! str_contains($path, '\\')
            && Storage::disk($disk)->exists($path);
    }
}
