@extends('layouts.learning')
@section('title', 'Payment confirmation — Kody')
@section('content')
<section class="review-page page-width wallet-page" data-payment-status="{{ route('wallet.purchase.status', $row->id) }}">
<p class="overline">SECURE CHECKOUT</p><h1>Your KodeBit purchase</h1>
<div class="wallet-summary"><article class="wallet-summary-card"><h2>Selected package</h2><div class="wallet-amount"><x-kody-art name="kodebit" size="64"/><strong>{{ number_format($row->kodebits) }} KB</strong></div><p>PHP {{ \App\Support\ExactMoney::decimal($row->gross_minor) }} · {{ ucfirst($row->package) }}</p></article>
<article class="wallet-summary-card"><h2>Server-confirmed payment status</h2><p class="status-pill" data-payment-state>{{ $row->state }}</p><p data-payment-feedback role="status">Your browser return is not proof of payment. Kody waits for the authenticated callback and independently verified provider result.</p>
<button class="button button-secondary button-small" type="button" data-payment-refresh><x-action-icon name="refresh"/> Check status</button></article></div>
<div class="studio-actions"><a class="button button-play" data-payment-checkout @if($paymentsReady && $row->state === 'Pending' && $row->checkout_url) href="{{ route('wallet.checkout', $row->id) }}" @else hidden @endif>Continue to Xendit <x-action-icon name="right"/></a>
<a class="button button-success" data-payment-receipt @if($row->lot_id) href="{{ route('wallet.receipt', $row->id) }}" @else hidden @endif><x-action-icon name="check"/> View payment receipt</a>
<a class="button button-secondary" href="{{ route('wallet.index') }}"><x-action-icon name="wallet"/> Back to Wallet</a></div>
<p class="field-hint transaction-reference">Purchase reference: {{ $row->id }}</p>
</section>
@endsection
