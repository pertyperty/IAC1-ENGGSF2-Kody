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
    private const PER_PAGE = 15;

    public function index(Request $request, NotificationDestinations $destinations): Response
    {
        $filters = $request->validate(['state' => ['nullable', 'in:all,unread,read']]);
        $state = $filters['state'] ?? 'all';
        $unreadCount = $request->user()->unreadNotifications()->count();
        $notifications = $request->user()->notifications()
            ->when($state === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($state === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();
        $feedbackLinks = $destinations->for($request->user(), $notifications->getCollection());

        return response()->view('content.notifications', compact('notifications', 'feedbackLinks', 'state', 'unreadCount'))->header('Cache-Control', 'no-store, private');
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $filters = $request->validate([
            'state' => ['nullable', 'in:all,unread,read'],
            'page' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
        ]);
        DB::transaction(function () use ($request, $notification): void {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            app(CurrentAccountSession::class)->assert($user, $request->session()->getId());
            $user->notifications()->whereKey($notification)->firstOrFail()->markAsRead();
        });

        $state = $filters['state'] ?? 'all';
        $page = (int) ($filters['page'] ?? 1);
        if ($page > 1) {
            $remaining = $request->user()->notifications()
                ->when($state === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->when($state === 'read', fn ($query) => $query->whereNotNull('read_at'))
                ->count();
            $page = min($page, max(1, (int) ceil($remaining / self::PER_PAGE)));
        }

        return redirect()->route('notifications.index', array_filter([
            'state' => $state === 'all' ? null : $state,
            'page' => $page > 1 ? $page : null,
        ]))->with('status', 'Update marked as read.');
    }
}
