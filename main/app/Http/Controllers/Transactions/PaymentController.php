<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\PurchaseKodeBitsRequest;
use App\Services\Documents\PdfDocuments;
use App\Services\Transactions\Payments;
use App\Services\Transactions\ProviderWebhooks;
use App\Services\Transactions\WalletLedger;
use App\Services\Transactions\Xendit\Client;
use App\Services\Transactions\Xendit\Readiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function purchase(PurchaseKodeBitsRequest $request, Payments $payments): RedirectResponse
    {
        $data = $request->validated();
        $id = $payments->purchase($request->user(), $request->session()->getId(), $data);

        return redirect()->route('wallet.purchase.show', $id)->with('status', 'Payment requested. The secure checkout will appear when it is ready.');
    }

    public function checkout(Request $request, string $purchase, Client $client): RedirectResponse
    {
        $row = DB::table('payment_purchases')->where('id', $purchase)->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($row->state === 'Pending' && $row->checkout_url !== null, 409);
        abort_unless(app(Readiness::class)->available(), 503, 'Live checkout is currently unavailable.');

        return redirect()->away($client->checkout($row->checkout_url))->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function webhook(Request $request, ProviderWebhooks $webhooks): Response
    {
        $webhooks->receive($request->header('x-callback-token', ''), $request->getContent(), $request->header('webhook-id'));

        return response('', 200);
    }

    public function show(Request $request, string $purchase): Response
    {
        $row = DB::table('payment_purchases')->where('id', $purchase)->where('user_id', $request->user()->id)->firstOrFail();

        $paymentsReady = app(Readiness::class)->available();

        return response()->view('transactions.purchase-status', compact('row', 'paymentsReady'))->header('Cache-Control', 'no-store, private');
    }

    public function status(Request $request, string $purchase): JsonResponse
    {
        $row = DB::table('payment_purchases')->where('id', $purchase)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(['state' => $row->state, 'verified' => $row->lot_id !== null,
            'available' => app(WalletLedger::class)->snapshot($request->user()->id)['available'],
            'checkout' => app(Readiness::class)->available() && $row->state === 'Pending' && $row->checkout_url !== null ? route('wallet.checkout', $row->id) : null,
            'receipt' => $row->lot_id !== null ? route('wallet.receipt', $row->id) : null])->header('Cache-Control', 'no-store, private');
    }

    public function receipt(Request $request, string $purchase): Response
    {
        $row = DB::table('payment_purchases')->where('id', $purchase)->where('user_id', $request->user()->id)->whereNotNull('lot_id')->firstOrFail();
        $notice = DB::table('financial_deliveries')->where('user_id', $request->user()->id)->where('reference', 'purchase:'.$row->id)->firstOrFail();
        $receipt = ['id' => $notice->id, 'title' => $notice->title, 'details' => json_decode($notice->details, true, flags: JSON_THROW_ON_ERROR),
            'issued_at' => $notice->created_at, 'purchase_reference' => $row->id];
        if ($request->boolean('pdf')) {
            return response(app(PdfDocuments::class)->render('transactions.receipt-pdf', compact('receipt')))->withHeaders([
                'Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="kody-receipt-'.$row->id.'.pdf"',
                'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
        }

        return response()->view('transactions.receipt', compact('receipt', 'row'))->header('Cache-Control', 'no-store, private');
    }
}
