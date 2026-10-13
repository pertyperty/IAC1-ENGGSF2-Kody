<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\BrowseAccountsRequest;
use App\Http\Requests\Administration\ChangeModeratorRequest;
use App\Http\Requests\Administration\CorrectProfileRequest;
use App\Http\Requests\Administration\EnforceAccountRequest;
use App\Models\User;
use App\Services\Administration\AccountEnforcement;
use App\Services\Administration\ModeratorAppointments;
use App\Services\Administration\SupportProfileCorrections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AccountGovernanceController extends Controller
{
    public function index(BrowseAccountsRequest $request): Response
    {
        $filters = $request->validated();
        if ($request->boolean('reset')) {
            $request->session()->forget('account_governance_filters');
            $filters = [];
        } elseif ($request->hasAny(['q', 'role', 'status', 'scope', 'per_page'])) {
            $request->session()->put('account_governance_filters', $filters);
        } else {
            $filters = $request->session()->get('account_governance_filters', []);
        }
        $query = User::select(['id', 'username', 'account_role', 'account_status', 'created_at']);
        if (($filters['q'] ?? '') !== '') {
            $literal = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']);
            $query->whereRaw("username ILIKE ? ESCAPE '\\'", ['%'.$literal.'%']);
        }
        foreach (['role' => 'account_role', 'status' => 'account_status'] as $parameter => $column) {
            if (($filters[$parameter] ?? '') !== '') {
                $query->where($column, $filters[$parameter]);
            }
        }
        $expanded = ($filters['scope'] ?? 'recent') === 'all' || ($filters['q'] ?? '') !== ''
            || ($filters['role'] ?? '') !== '' || ($filters['status'] ?? '') !== '';
        $query->orderByDesc('created_at')->orderByDesc('id');
        if ($expanded) {
            $accounts = $query->paginate((int) ($filters['per_page'] ?? 10))->appends($filters);
        } else {
            $recent = $query->limit(10)->get();
            $accounts = new LengthAwarePaginator($recent, $recent->count(), 10, 1, ['path' => $request->url()]);
        }

        return response()->view('account.governance-index', compact('accounts', 'filters', 'expanded'))->header('Cache-Control', 'no-store, private');
    }

    public function show(User $account): Response
    {
        Gate::authorize('viewAny', User::class);
        $history = DB::table('account_enforcements')->where('user_id', $account->id)->latest('created_at')->paginate(10)->withQueryString();
        $roleHistory = DB::table('account_role_changes')->where('user_id', $account->id)->latest('created_at')->paginate(10, ['*'], 'roles_page')->withQueryString();
        $supportHistory = DB::table('account_support_corrections')->where('user_id', $account->id)->latest('created_at')->paginate(10, ['*'], 'support_page')->withQueryString();

        return response()->view('account.governance-account', compact('account', 'history', 'roleHistory', 'supportHistory'))->header('Cache-Control', 'no-store, private');
    }

    public function enforce(EnforceAccountRequest $request, User $account, AccountEnforcement $enforcement): RedirectResponse
    {
        $enforcement->change($request->user(), $request->session()->getId(), $account, $request->validated());

        return redirect()->route('account-governance.show', $account)->with('status', 'Account action applied. The account holder will be notified.');
    }

    public function moderator(ChangeModeratorRequest $request, User $account, ModeratorAppointments $appointments): RedirectResponse
    {
        $appointments->change($request->user(), $request->session()->getId(), $account, $request->validated());

        return redirect()->route('account-governance.show', $account)->with('status', 'Moderator role action applied. The account holder must sign in again and will be notified.');
    }

    public function correctProfile(CorrectProfileRequest $request, User $account, SupportProfileCorrections $corrections): RedirectResponse
    {
        $changed = $corrections->correct($request->user(), $request->session()->getId(), $account, $request->validated());

        return redirect()->route('account-governance.show', $account)->with('status', $changed
            ? 'Support correction saved. The account holder must sign in again and will be notified.' : 'No profile fields changed.');
    }
}
