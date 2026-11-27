<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\LearningCourse;
use App\Services\Gamification\Achievements;
use App\Services\Transactions\WalletLedger;
use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function index(Request $request, WalletLedger $ledger, Achievements $achievements): Response
    {
        $filters = $request->validate(['direction' => ['nullable', 'in:credit,debit']]);
        $id = $request->user()->id;
        $wallet = $ledger->snapshot($id);
        $rank = $achievements->snapshot($id);
        $operations = DB::table('wallet_operations')->where('user_id', $id)
            ->when(($filters['direction'] ?? '') === 'credit', fn ($query) => $query->where('kodebits', '>', 0))
            ->when(($filters['direction'] ?? '') === 'debit', fn ($query) => $query->where('kodebits', '<', 0))->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'history')->withQueryString();
        $earnings = DB::table('publisher_earnings as earnings')->join('content_purchases as sales', 'sales.id', '=', 'earnings.purchase_id')
            ->where('earnings.user_id', $id)->orderByDesc('earnings.created_at')->orderByDesc('earnings.id')
            ->paginate(15, ['earnings.*', 'sales.kodebits as sold_kb', 'sales.value_minor as sale_value_minor'], 'earnings')->withQueryString();
        $purchases = DB::table('payment_purchases')->where('user_id', $id)->orderByDesc('created_at')->limit(10)->get(['id', 'package', 'gross_minor', 'state', 'checkout_url', 'lot_id']);
        $confirmationId = (string) Str::uuid();
        $readiness = app(Readiness::class);
        $sandbox = $readiness->sandboxAvailable();
        $paymentsReady = $readiness->checkoutAvailable() && $request->user()->can('viewLearning', LearningCourse::class);

        return response()->view('transactions.wallet', compact('wallet', 'rank', 'operations', 'earnings', 'purchases', 'confirmationId', 'paymentsReady', 'sandbox', 'filters'))->header('Cache-Control', 'no-store, private');
    }
}
