<?php

namespace App\Http\Controllers\Account;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\InstructorApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $user->loadMissing('creatorVerification');
        [$local, $domain] = array_pad(explode('@', $user->email, 2), 2, null);
        $profile = [
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $domain === null ? 'Not available' : mb_substr($local, 0, 1).'***@'.$domain,
            'role' => $user->account_role->value,
            'status' => $user->account_status->value,
            'joined_at' => $user->created_at?->format('F j, Y'),
        ];

        $application = InstructorApplication::where('user_id', $user->id)->first(['verification_status', 'verification_notes', 'verified_at']);
        $badgeSummary = null;
        if (in_array($user->account_role, [Role::Instructor, Role::Contributor], true)) {
            $pendingRequest = DB::table('creator_badge_requests')
                ->where('user_id', $user->id)
                ->where('role', $user->account_role->value)
                ->whereNull('resolved_at')
                ->exists();
            $badgeSummary = [
                'active' => $user->hasCreatorBadge(),
                'pending' => $pendingRequest,
            ];
        }

        return response()->view('account.profile', compact('profile', 'application', 'badgeSummary'))->header('Cache-Control', 'no-store, private');
    }
}
