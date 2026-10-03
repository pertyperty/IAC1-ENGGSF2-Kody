<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\BrowseAccountsRequest;
use App\Http\Requests\Administration\ChangeModeratorRequest;
use App\Http\Requests\Administration\EnforceAccountRequest;
use App\Models\User;
use App\Services\Administration\AccountEnforcement;
use App\Services\Administration\ModeratorAppointments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AccountGovernanceController extends Controller
{
    public function index(BrowseAccountsRequest $request): Response
    {
        $filters = $request->validated();
        if ($request->hasAny(['q', 'role', 'status'])) {
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
        $accounts = $query->orderBy('id')->paginate(20)->appends($filters);

        return response()->view('account.governance-index', compact('accounts', 'filters'))->header('Cache-Control', 'no-store, private');
    }

    public function show(User $account): Response
    {
        Gate::authorize('viewAny', User::class);
        $history = DB::table('account_enforcements')->where('user_id', $account->id)->latest('created_at')->paginate(10);
        $roleHistory = DB::table('account_role_changes')->where('user_id', $account->id)->latest('created_at')->paginate(10, ['*'], 'roles_page');

        return response()->view('account.governance-account', compact('account', 'history', 'roleHistory'))->header('Cache-Control', 'no-store, private');
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
}
