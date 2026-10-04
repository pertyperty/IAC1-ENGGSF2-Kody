<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\PurchaseKodeBitsRequest;
use App\Services\Transactions\Payments;
use App\Services\Transactions\ProviderWebhooks;
use App\Services\Transactions\Xendit\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function purchase(PurchaseKodeBitsRequest $request, Payments $payments): RedirectResponse
    {
        $data = $request->validated();
        $payments->purchase($request->user(), $request->session()->getId(), $data);

        return redirect()->route('wallet.index')->with('status', 'Payment requested. The secure checkout will appear when it is ready.');
    }

    public function checkout(Request $request, string $purchase, Client $client): RedirectResponse
    {
        $row = DB::table('payment_purchases')->where('id', $purchase)->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($row->state === 'Pending' && $row->checkout_url !== null, 409);

        return redirect()->away($client->checkout($row->checkout_url))->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function webhook(Request $request, ProviderWebhooks $webhooks): Response
    {
        $webhooks->receive($request->header('x-callback-token', ''), $request->getContent(), $request->header('webhook-id'));

        return response('', 200);
    }
}
