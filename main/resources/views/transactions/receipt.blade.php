@extends('layouts.learning')
@section('title', 'Payment receipt — Kody')
@section('content')
<section class="review-page page-width wallet-page"><p class="overline">VERIFIED PAYMENT</p><h1>Your payment receipt</h1>
<div class="wallet-summary"><article class="wallet-summary-card"><h2>Amount paid</h2><div class="wallet-amount"><strong>PHP {{ \App\Support\ExactMoney::decimal($receipt['details']['amount_minor']) }}</strong></div><p>{{ number_format($receipt['details']['kodebits']) }} KodeBits purchased</p></article>
<article class="wallet-summary-card"><h2>Confirmation</h2><p>{{ \Carbon\CarbonImmutable::parse($receipt['issued_at'])->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p><p>Current purchase state: {{ $row->state }}</p><p class="field-hint">This acknowledges the verified purchase. It is not a tax invoice; later refunds remain separate ledger events.</p></article></div>
<dl class="profile-details"><dt>Receipt</dt><dd>{{ $receipt['id'] }}</dd><dt>Purchase</dt><dd>{{ $receipt['purchase_reference'] }}</dd></dl>
<div class="studio-actions"><a class="button button-play" href="{{ route('wallet.receipt', [$row->id, 'pdf' => 1]) }}" data-navigation-download><x-action-icon name="download"/> Download PDF</a><a class="button button-secondary" href="{{ route('wallet.index') }}"><x-action-icon name="wallet"/> Your Wallet</a></div></section>
@endsection
