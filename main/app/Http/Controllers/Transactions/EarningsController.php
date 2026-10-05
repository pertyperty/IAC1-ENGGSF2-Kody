<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Services\Transactions\PublisherSettlements;
use App\Services\Transactions\Refunds;
use App\Services\Transactions\WalletClosure;
use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EarningsController extends Controller
{
    public function index(Request $request): Response
    {
        $id = $request->user()->id;
        $payouts = DB::table('payout_requests')->where('user_id', $id)->orderByDesc('created_at')->paginate(10, ['id', 'gross_minor', 'fee_minor', 'state', 'review_notes', 'created_at'], 'payouts');
        $refunds = DB::table('refund_requests')->where('user_id', $id)->orderByDesc('created_at')->paginate(10, ['id', 'kind', 'state', 'reason', 'review_notes'], 'refunds');
        $confirmationId = (string) Str::uuid();
        $ready = app(Readiness::class)->available();
        $accessSales = DB::table('content_purchases')->where('user_id', $id)->where('status', 'Active')->where('created_at', '>=', now()->subDays(7))->orderByDesc('created_at')->limit(20)->get();
        $topups = DB::table('payment_purchases')->where('user_id', $id)->where('state', 'Succeeded')->where('created_at', '>=', now()->subDays(14))->orderByDesc('created_at')->limit(20)->get(['id', 'package']);
        $closure = app(WalletClosure::class)->snapshot($id);

        return response()->view('transactions.earnings', compact('payouts', 'refunds', 'confirmationId', 'ready', 'accessSales', 'topups', 'closure'))->header('Cache-Control', 'no-store, private');
    }

    public function claim(Request $request, PublisherSettlements $settlements): RedirectResponse
    {
        $data = $request->validate(['confirmation_id' => ['required', 'uuid'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        $kb = $settlements->claim($request->user(), $request->session()->getId(), $data);

        return redirect()->route('wallet.index')->with('status', $kb.' earned KodeBits added to your wallet.');
    }

    public function payout(Request $request, PublisherSettlements $settlements): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'string', 'regex:/^(0|[1-9][0-9]{0,7})(\.[0-9]{1,2})?$/D'],
            'quoted_fee_minor' => ['required', 'integer', 'min:0'], 'confirmation_id' => ['required', 'uuid'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted'],
            'given_name' => ['required', 'string', 'max:50'], 'surname' => ['required', 'string', 'max:50'], 'mobile' => ['required', 'string', 'max:11'],
            'street' => ['required', 'string', 'max:255'], 'city' => ['required', 'string', 'max:255'], 'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:4'], 'account_holder_name' => ['required', 'string', 'max:255']]);
        $settlements->request($request->user(), $request->session()->getId(), $data);

        return redirect()->route('earnings.index')->with('status', 'Payout submitted for recipient verification. Earnings are reserved until a verified outcome.');
    }

    public function refund(Request $request, string $kind, string $target, Refunds $refunds): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500'], 'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        $refunds->request($request->user(), $request->session()->getId(), $kind, $target, $data);

        return redirect()->route('earnings.index')->with('status', 'Refund submitted for review. Your history is preserved.');
    }

    public function relinquish(Request $request, WalletClosure $closure): RedirectResponse
    {
        $data = $request->validate(['fingerprint' => ['required', 'string', 'size:64'], 'confirmation_phrase' => ['required', 'string', 'max:50'],
            'current_password' => ['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']]);
        $closure->relinquish($request->user(), $request->session()->getId(), $data);

        return redirect()->route('earnings.index')->with('status', 'Your explicitly selected balances were permanently relinquished. Financial history remains retained.');
    }
}
