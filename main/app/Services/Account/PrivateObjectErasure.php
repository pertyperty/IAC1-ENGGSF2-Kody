<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PrivateObjectErasure
{
    public function erase(string $diskName, string $path): void
    {
        $disk = Storage::disk($diskName);
        if (config('filesystems.disks.'.$diskName.'.driver') !== 's3') {
            if (! $disk->delete($path) || $disk->exists($path)) {
                throw new RuntimeException('Private object erasure failed.');
            }

            return;
        }
        // A normal S3 delete only creates a marker when versioning is enabled.
        // Remove every version of this exact generated key, never a whole prefix.
        if (! method_exists($disk, 'getClient')) {
            throw new RuntimeException('Version-aware private S3 storage is required.');
        }
        $client = $disk->getClient();
        $bucket = config('filesystems.disks.'.$diskName.'.bucket');
        $key = $disk->path($path);
        $status = $client->getBucketVersioning(['Bucket' => $bucket])['Status'] ?? null;
        if (! in_array($status, ['Enabled', 'Suspended'], true)) {
            if (! $disk->delete($path) || $disk->exists($path)) {
                throw new RuntimeException('Private object erasure failed.');
            }

            return;
        }
        $request = ['Bucket' => $bucket, 'Prefix' => $key];
        $pages = 0;
        do {
            $result = $client->listObjectVersions($request);
            $objects = [];
            foreach (array_merge($result['Versions'] ?? [], $result['DeleteMarkers'] ?? []) as $version) {
                if (($version['Key'] ?? null) === $key && is_string($version['VersionId'] ?? null)) {
                    $objects[] = ['Key' => $key, 'VersionId' => $version['VersionId']];
                }
            }
            foreach (array_chunk($objects, 1000) as $chunk) {
                $deleted = $client->deleteObjects(['Bucket' => $bucket, 'Delete' => ['Objects' => $chunk, 'Quiet' => true]]);
                if (! empty($deleted['Errors'])) {
                    throw new RuntimeException('Private object versions remain retained.');
                }
            }
            if (++$pages > 1000) {
                throw new RuntimeException('Private version erasure exceeded its work bound.');
            }
            $request['KeyMarker'] = $result['NextKeyMarker'] ?? '';
            if (! empty($result['NextVersionIdMarker'])) {
                $request['VersionIdMarker'] = $result['NextVersionIdMarker'];
            } else {
                unset($request['VersionIdMarker']);
            }
            if (($result['IsTruncated'] ?? false) && $request['KeyMarker'] === '') {
                throw new RuntimeException('Incomplete version listing.');
            }
        } while ($result['IsTruncated'] ?? false);
        // Verify all versions are gone, including markers. Errors never mark erasure complete.
        $request = ['Bucket' => $bucket, 'Prefix' => $key];
        $pages = 0;
        do {
            $remaining = $client->listObjectVersions($request);
            foreach (array_merge($remaining['Versions'] ?? [], $remaining['DeleteMarkers'] ?? []) as $version) {
                if (($version['Key'] ?? null) === $key) {
                    throw new RuntimeException('Private object version remains.');
                }
            }
            if (++$pages > 1000) {
                throw new RuntimeException('Private version verification exceeded its work bound.');
            }
            $request['KeyMarker'] = $remaining['NextKeyMarker'] ?? '';
            if (! empty($remaining['NextVersionIdMarker'])) {
                $request['VersionIdMarker'] = $remaining['NextVersionIdMarker'];
            } else {
                unset($request['VersionIdMarker']);
            }
            if (($remaining['IsTruncated'] ?? false) && $request['KeyMarker'] === '') {
                throw new RuntimeException('Incomplete version verification.');
            }
        } while ($remaining['IsTruncated'] ?? false);
    }
}
