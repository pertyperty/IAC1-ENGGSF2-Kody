<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Account\CurrentAccountSession;
use App\Services\Engagement\NotificationDestinations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationDestinations $destinations): Response
    {
        $notifications = $request->user()->notifications()->orderByDesc('id')->paginate(15);
        $feedbackLinks = $destinations->for($request->user(), $notifications->getCollection());

        return response()->view('content.notifications', compact('notifications', 'feedbackLinks'))->header('Cache-Control', 'no-store, private');
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        DB::transaction(function () use ($request, $notification): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
            $user->notifications()->whereKey($notification)->firstOrFail()->markAsRead();
        });

        return redirect()->route('notifications.index')->with('status', 'Update marked as read.');
    }
}
