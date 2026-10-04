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
        $id = $request->user()->id;
        $wallet = $ledger->snapshot($id);
        $rank = $achievements->snapshot($id);
        $operations = DB::table('wallet_operations')->where('user_id', $id)->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'history');
        $earnings = DB::table('publisher_earnings')->where('user_id', $id)->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'earnings');
        $purchases = DB::table('payment_purchases')->where('user_id', $id)->orderByDesc('created_at')->limit(10)->get(['id', 'package', 'gross_minor', 'state', 'checkout_url']);
        $confirmationId = (string) Str::uuid();
        $paymentsReady = app(Readiness::class)->available() && $request->user()->can('viewLearning', LearningCourse::class);

        return response()->view('transactions.wallet', compact('wallet', 'rank', 'operations', 'earnings', 'purchases', 'confirmationId', 'paymentsReady'))->header('Cache-Control', 'no-store, private');
    }
}
