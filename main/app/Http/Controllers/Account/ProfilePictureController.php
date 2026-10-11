<?php

namespace App\Http\Controllers\Account;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ProfilePictureRequest;
use App\Models\User;
use App\Services\Account\ProfilePictures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfilePictureController extends Controller
{
    public function update(ProfilePictureRequest $request, ProfilePictures $pictures): RedirectResponse
    {
        $pictures->update($request->user(), $request->session()->getId(), (int) $request->validated('profile_version'), $request->file('picture'));

        return redirect()->route('account.edit')->with('status', $request->boolean('remove_picture') ? 'Profile picture removed.' : 'Your new profile picture is ready.');
    }

    public function show(User $user): Response
    {
        abort_unless($user->account_status === AccountStatus::Active && $user->email_verified_at !== null && $user->profile_picture !== null, 404);
        $asset = $user->profile_picture;
        abort_unless(in_array($asset['disk'] ?? null, ['local', 's3'], true)
            && config('filesystems.disks.'.$asset['disk'].'.visibility') !== 'public'
            && preg_match('~^profile-pictures/[0-9a-f-]{36}\.jpg$~D', $asset['path'] ?? '')
            && Storage::disk($asset['disk'])->exists($asset['path']), 404);

        return response(Storage::disk($asset['disk'])->get($asset['path']), 200,
            ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }
}
