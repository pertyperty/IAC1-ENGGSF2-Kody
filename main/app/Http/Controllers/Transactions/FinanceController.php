<?php

namespace App\Http\Controllers\Transactions;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Services\Administration\AuditRecorder;
use App\Services\Operations\BudgetAdmission;
use App\Services\Transactions\FinanceGovernance;
use App\Services\Transactions\FinancialRecovery;
use App\Services\Transactions\PublisherSettlements;
use App\Services\Transactions\Refunds;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->account_role === Role::Administrator, 403);
        $payouts = DB::table('payout_requests')->whereIn('state', ['PendingReview', 'Review'])->orderBy('created_at')->paginate(15, ['id', 'user_id', 'gross_minor', 'state'], 'payouts');
        $refunds = DB::table('refund_requests')->whereIn('state', ['PendingReview', 'Review'])->orderBy('created_at')->paginate(15, ['id', 'user_id', 'kind', 'state'], 'refunds');
        $callbacks = DB::table('provider_webhooks')->where('state', 'Review')->orderBy('created_at')->limit(30)->get(['id', 'event', 'object_id']);
        $deliveries = DB::table('financial_deliveries')->where('state', 'Review')->orderBy('created_at')->limit(30)->get(['id', 'user_id', 'title']);
        $remedies = DB::table('financial_remedy_cases')->whereIn('state', ['Open', 'Review'])->orderBy('created_at')->paginate(15, ['*'], 'remedies');
        $cash = DB::table('economy_locks')->find(1);
        $month = CarbonImmutable::now('Asia/Manila')->startOfMonth()->toDateString();
        $budget = DB::table('operations_budget_reports')->where('month', $month)->first();
        $summary = [
            'wallet_backing' => (int) DB::table('wallet_lots')->sum('remaining_minor'),
            'unclaimed_creator' => (int) DB::table('publisher_earnings')->where('status', 'Available')->selectRaw('coalesce(sum(amount_minor - claimed_minor),0) AS total')->value('total'),
            'confirmed_purchases' => (int) DB::table('payment_purchases')->whereIn('state', ['Succeeded', 'Refunding'])->sum('gross_minor'),
            'confirmed_refunds' => (int) DB::table('payment_purchases')->where('state', 'Refunded')->sum('gross_minor'),
        ];
        $cashHistory = DB::table('platform_cash_entries')->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'cash');

        return response()->view('transactions.finance', compact('payouts', 'refunds', 'callbacks', 'deliveries', 'remedies', 'cash', 'budget', 'summary', 'cashHistory'))->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, string $kind, string $operation): Response
    {
        abort_unless($request->user()->account_role === Role::Administrator, 403);
        $table = $kind === 'payout' ? 'payout_requests' : 'refund_requests';
        $row = DB::table($table)->find($operation) ?? abort(404);
        $recipient = $kind === 'payout' ? json_decode(Crypt::decryptString($row->recipient), true, flags: JSON_THROW_ON_ERROR) : null;
        if ($recipient !== null) {
            app(AuditRecorder::class)->record($request->user()->id, $row->user_id, 'payout.recipient_viewed', 'payout_request', $row->id);
        }

        return response()->view('transactions.finance-review', compact('kind', 'row', 'recipient'))->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function review(Request $request, string $kind, string $operation): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['Approved', 'Rejected'])], 'review_notes' => ['required', 'string', 'max:500'],
            'recipient_verified' => ['sometimes', 'accepted'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        if ($kind === 'payout') {
            app(PublisherSettlements::class)->review($request->user(), $request->session()->getId(), $operation, $data);
        } else {
            app(Refunds::class)->review($request->user(), $request->session()->getId(), $operation, $data);
        }

        return redirect()->route('finance.index')->with('status', 'Decision saved. Provider-backed requests still await a verified outcome.');
    }

    public function fund(Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})(\.[0-9]{1,2})?$/D'], 'reference' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/D'],
            'evidence' => ['required', 'string', 'max:500'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        app(FinanceGovernance::class)->cash($request->user(), $request->session()->getId(), $data);

        return redirect()->route('finance.index')->with('status', 'Attested platform funding recorded.');
    }

    public function budget(Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})(\.[0-9]{1,2})?$/D'], 'record_version' => ['required', 'integer', 'min:0'],
            'source' => ['required', 'string', 'max:500'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        app(BudgetAdmission::class)->report($request->user(), $request->session()->getId(), $data);

        return redirect()->route('finance.index')->with('status', 'Monthly operating-cost report recorded.');
    }

    public function remedy(Request $request): RedirectResponse
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:100'], 'message' => ['required', 'string', 'max:1000']]);
        app(FinanceGovernance::class)->remedy($request->user(), $request->session()->getId(), $data);

        return redirect()->route('earnings.index')->with('status', 'Exceptional remedy recorded for Administrator review.');
    }

    public function reviewRemedy(Request $request, string $case): RedirectResponse
    {
        $data = $request->validate(['state' => ['required', Rule::in(['Review', 'Resolved'])], 'review_notes' => ['required', 'string', 'max:1000'],
            'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        app(FinanceGovernance::class)->reviewRemedy($request->user(), $request->session()->getId(), $case, $data);

        return redirect()->route('finance.index')->with('status', 'Remedy decision recorded. This does not attest a provider refund or change financial balances.');
    }

    public function expense(Request $request): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})(\.[0-9]{1,2})?$/D'],
            'reference' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/D'], 'evidence' => ['required', 'string', 'max:500'],
            'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        app(FinanceGovernance::class)->cash($request->user(), $request->session()->getId(), $data, true);

        return redirect()->route('finance.index')->with('status', 'Actual platform expense recorded against available cash.');
    }

    public function retry(Request $request, string $kind, string $operation): RedirectResponse
    {
        $data = $request->validate(['notes' => ['required', 'string', 'max:500'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        app(FinancialRecovery::class)->retry($request->user(), $request->session()->getId(), $kind, $operation, $data);

        return redirect()->route('finance.index')->with('status', 'Explicit reconciliation retry recorded. Provider proof is still required.');
    }
}
