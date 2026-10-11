<?php

namespace App\Services\Account;

use App\Models\User;
use App\Services\Administration\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProfilePictures
{
    public function update(User $actor, string $sessionId, int $version, ?UploadedFile $file): void
    {
        $bytes = $file === null ? null : $this->thumbnail($file);
        $asset = null;
        try {
            DB::transaction(function () use ($actor, $sessionId, $version, $bytes, &$asset): void {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                app(CurrentAccountSession::class)->assert($user, $sessionId);
                Gate::forUser($user)->authorize('update', $user);
                if ($user->profile_version !== $version) {
                    throw ValidationException::withMessages(['profile_version' => 'Your profile changed. Reload before saving a picture.']);
                }
                if ($bytes !== null) {
                    $disk = config('profile-picture.disk');
                    if (! in_array($disk, ['local', 's3'], true) || config('filesystems.disks.'.$disk.'.visibility') === 'public') {
                        throw new RuntimeException('Profile pictures require private local or S3 storage.');
                    }
                    $asset = ['disk' => $disk, 'path' => 'profile-pictures/'.Str::uuid().'.jpg'];
                    if (! Storage::disk($disk)->put($asset['path'], $bytes, ['visibility' => 'private'])) {
                        throw new RuntimeException('The picture could not be stored.');
                    }
                }
                if ($user->profile_picture !== null) {
                    app(PrivateFileRemoval::class)->queue($user->id, $user->profile_picture['disk'], $user->profile_picture['path']);
                }
                $user->forceFill(['profile_picture' => $asset, 'profile_version' => $version + 1])->save();
                app(AuditRecorder::class)->record($user->id, $user->id, 'account.picture-updated', 'user', (string) $user->id,
                    ['removed' => $asset === null, 'version' => $user->profile_version]);
            });
        } catch (Throwable $exception) {
            if ($asset !== null) {
                try {
                    DB::transaction(fn () => app(PrivateFileRemoval::class)->queue($actor->id, $asset['disk'], $asset['path']));
                } catch (Throwable) {
                    Log::error('Orphan picture cleanup could not be queued.', ['user_id' => $actor->id]);
                }
            }
            if ($exception instanceof ValidationException || $exception instanceof AuthorizationException) {
                throw $exception;
            }
            Log::error('Profile picture update failed.', ['user_id' => $actor->id]);
            throw new RuntimeException('Your picture could not be saved. Please try again.');
        }
    }

    private function thumbnail(UploadedFile $file): string
    {
        $bytes = file_get_contents($file->getRealPath());
        $size = @getimagesizefromstring($bytes);
        if ($file->getSize() > 2 * 1024 * 1024 || $size === false || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)
            || $size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 6000000) {
            throw ValidationException::withMessages(['picture' => 'Choose a JPEG or PNG up to 2 MB and 6 megapixels (maximum 4096 pixels per side).']);
        }
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            throw ValidationException::withMessages(['picture' => 'This image could not be decoded. Choose another JPEG or PNG.']);
        }
        $target = imagecreatetruecolor(256, 256);
        try {
            imagefill($target, 0, 0, imagecolorallocate($target, 238, 242, 249));
            $side = min($size[0], $size[1]);
            imagecopyresampled($target, $source, 0, 0, (int) (($size[0] - $side) / 2), (int) (($size[1] - $side) / 2), 256, 256, $side, $side);
            ob_start();
            imagejpeg($target, null, 85);

            return ob_get_clean();
        } finally {
            imagedestroy($source);
            imagedestroy($target);
        }
    }
}
