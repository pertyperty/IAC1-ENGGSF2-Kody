<?php

namespace App\Services\Account;

use App\Models\ContributorApplication;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CredentialDocuments
{
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

    public const ALLOWED_MIME_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function validateUpload(UploadedFile $file): void
    {
        $maxKilobytes = (int) config('account.credentials.max_kilobytes', 5120);
        $maxLabel = number_format($maxKilobytes / 1024, 1);
        $extension = strtolower($file->getClientOriginalExtension());
        $expectedMime = self::ALLOWED_MIME_TYPES[$extension] ?? null;
        $realPath = $file->getRealPath();
        $size = $file->getSize();

        if ($expectedMime === null || $realPath === false || $size === false || $size <= 0 || $size > $maxKilobytes * 1024) {
            $this->invalidUpload($maxLabel);
        }

        if ($file->getMimeType() !== $expectedMime) {
            $this->invalidUpload($maxLabel);
        }

        $bytes = @file_get_contents($realPath);
        if (! is_string($bytes) || $bytes === '') {
            $this->invalidUpload($maxLabel);
        }

        if ($extension === 'pdf') {
            if (! str_starts_with($bytes, '%PDF-')
                || ! str_contains(substr($bytes, -2048), '%%EOF')
                || preg_match('~/\s*(JavaScript|JS|Launch|EmbeddedFile|OpenAction|Encrypt)\b~', $bytes) === 1) {
                $this->invalidUpload($maxLabel);
            }

            return;
        }

        $image = @getimagesizefromstring($bytes);
        $expectedType = $extension === 'png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG;
        if ($image === false || $image[2] !== $expectedType || $image[0] <= 0 || $image[1] <= 0
            || $image[0] > 8000 || $image[1] > 8000 || ($image[0] * $image[1]) > 25000000) {
            $this->invalidUpload($maxLabel);
        }
    }

    public function available(InstructorApplication|ContributorApplication $application): bool
    {
        return $this->fileExists($application->credential_disk, $application->credential_path, $this->kind($application));
    }

    /**
     * @return array{available: bool, extension: string, mime: ?string, size: ?int, previewable: bool}
     */
    public function metadata(InstructorApplication|ContributorApplication $application, ?int $version = null): array
    {
        $kind = $this->kind($application);
        $document = $this->resolveDocument($application, $version);
        if ($document === null) {
            return ['available' => false, 'extension' => '', 'mime' => null, 'size' => null, 'previewable' => false];
        }
        $disk = (string) $document->credential_disk;
        $path = (string) $document->credential_path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::ALLOWED_MIME_TYPES[$extension] ?? null;
        $available = $this->fileExists($disk, $path, $kind);
        $size = null;
        if ($available) {
            try {
                $size = Storage::disk($disk)->size($path);
            } catch (\Throwable) {
                $size = null;
            }
        }

        return [
            'available' => $available,
            'extension' => $extension,
            'mime' => $mime,
            'size' => $size,
            'previewable' => $available && $mime !== null,
        ];
    }

    public function download(User $actor, InstructorApplication|ContributorApplication $application, ?int $version = null): StreamedResponse
    {
        Gate::forUser($actor)->authorize('view', $application);
        $kind = $this->kind($application);
        $document = $this->resolveDocument($application, $version);
        abort_if($document === null, 404);
        $disk = (string) $document->credential_disk;
        $path = (string) $document->credential_path;
        abort_unless($this->fileExists($disk, $path, $kind), 404);

        app(AuditRecorder::class)->record($actor->id, $application->user_id, $kind.'_application.credential_downloaded',
            $kind.'_application', (string) $application->id, $version === null ? [] : ['application_version' => $version]);

        return Storage::disk($disk)->download($path, $kind.'-credential'.($version === null ? '' : '-v'.$version).'.'.pathinfo($path, PATHINFO_EXTENSION),
            ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'no-store, private']);
    }

    public function inline(User $actor, InstructorApplication|ContributorApplication $application, ?int $version = null): StreamedResponse
    {
        Gate::forUser($actor)->authorize('view', $application);
        $kind = $this->kind($application);
        $document = $this->resolveDocument($application, $version);
        abort_if($document === null, 404);
        $disk = (string) $document->credential_disk;
        $path = (string) $document->credential_path;
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::ALLOWED_MIME_TYPES[$extension] ?? null;
        abort_unless($mime !== null && $this->fileExists($disk, $path, $kind), 404);

        app(AuditRecorder::class)->record($actor->id, $application->user_id, $kind.'_application.credential_viewed',
            $kind.'_application', (string) $application->id, $version === null ? [] : ['application_version' => $version]);

        return Storage::disk($disk)->response(
            $path,
            $kind.'-credential'.($version === null ? '' : '-v'.$version).'.'.$extension,
            [
                'Content-Type' => $mime,
                'Content-Security-Policy' => "sandbox; default-src 'none'",
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Cache-Control' => 'no-store, private',
            ],
            'inline',
        );
    }

    private function resolveDocument(InstructorApplication|ContributorApplication $application, ?int $version): ?object
    {
        if ($version === null) {
            return $application;
        }
        if (! $application instanceof InstructorApplication) {
            return null;
        }

        return DB::table('instructor_application_versions')
            ->where('instructor_application_id', $application->id)
            ->where('application_version', $version)
            ->first();
    }

    private function invalidUpload(string $maxLabel): never
    {
        throw ValidationException::withMessages([
            'credential_document' => "Upload a valid PDF, JPEG, or PNG up to {$maxLabel} MB. Password-protected, executable, or corrupted files are not accepted.",
        ]);
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
