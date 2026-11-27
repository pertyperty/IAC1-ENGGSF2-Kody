@extends('layouts.learning')
@section('title', 'Earnings and refunds — Kody')
@section('content')
<section class="review-page page-width"><p class="overline">EARNINGS &amp; REFUNDS</p><h1>Your earnings and refunds</h1><p><a class="quiet-link" href="{{ route('wallet.index') }}"><x-action-icon name="left"/> Wallet and sales history</a></p>

@if($errors->any())<div class="studio-errors" role="alert">{{ $errors->first() }}</div>@endif
@if(auth()->user()->account_role === \App\Enums\Role::Contributor || ($payoutSummary && $closure['creator_kb_milli'] > 0))
<details class="lesson-note"><summary>Claim matured Contributor KodeBits</summary><p>Whole KodeBits from eligible Contributor sales can be claimed after 14 days. Fractional earnings stay saved.</p><form class="studio-form" method="post" action="{{ route('earnings.claim') }}">@csrf<input type="hidden" name="confirmation_id" value="{{ $confirmationId }}">@include('transactions.password-confirmation')<button class="button button-play">Claim available whole KB</button></form></details>@endif
@if($payoutSummary)
<section aria-labelledby="cash-earnings-title">
    <h2 id="cash-earnings-title">Instructor cash earnings</h2>
    <div class="wallet-summary cash-earnings-summary">
        <article class="wallet-summary-card"><h3>Available to withdraw</h3><strong>PHP {{ \App\Support\ExactMoney::signedDecimal($payoutSummary['available']) }}</strong></article>
        <article class="wallet-summary-card"><h3>Still maturing</h3><strong>PHP {{ \App\Support\ExactMoney::signedDecimal($payoutSummary['pending']) }}</strong></article>
        <article class="wallet-summary-card"><h3>Reserved for payouts</h3><strong>PHP {{ \App\Support\ExactMoney::signedDecimal($payoutSummary['reserved']) }}</strong></article>
    </div>
    <p>Sales mature after {{ config('economy.maturity_days') }} days. Your cash share is {{ config('economy.creator_basis_points') / 100 }}% of the value behind the KodeBits spent, after payment fees. Different packages can give KodeBits different backing values.</p>
    <p>Minimum request: <strong>PHP {{ \App\Support\ExactMoney::decimal($payoutSummary['minimum']) }}</strong>.
        @if($payoutSummary['shortfall'] > 0) You need PHP {{ \App\Support\ExactMoney::decimal($payoutSummary['shortfall']) }} more in available earnings to request a payout. @endif
    </p>
    @if(!$ready)
        <p class="lesson-note">Cash payouts are unavailable on this instance. Your earnings history is still available.</p>
    @elseif($payoutSummary['shortfall'] === 0)
        <details class="lesson-note cash-payout" @if($errors->any()) open @endif><summary>Request a PHP payout</summary>
        <p>An Administrator reviews your GCash recipient details. If you withdraw your full balance now: PHP {{ \App\Support\ExactMoney::signedDecimal($payoutSummary['available']) }} minus PHP {{ \App\Support\ExactMoney::decimal($payoutSummary['fee']) }} fee = <strong>PHP {{ \App\Support\ExactMoney::signedDecimal($payoutSummary['available'] - $payoutSummary['fee']) }}</strong> to the recipient.</p>
        <form class="studio-form" data-payout-quote data-minimum="{{ $payoutSummary['minimum'] }}" data-available="{{ $payoutSummary['available'] }}" method="post" action="{{ route('earnings.payout') }}">@csrf<input type="hidden" name="confirmation_id" value="{{ $confirmationId }}"><input type="hidden" name="quoted_fee_minor" value="{{ $payoutSummary['fee'] }}"><label>Requested PHP amount<input name="amount" value="{{ old('amount') }}" inputmode="decimal" required maxlength="11" aria-describedby="payout-net-quote"></label>
        <p id="payout-net-quote" role="status" data-payout-net>Enter an amount to see what the recipient would receive.</p>
@foreach(['given_name' => 'Recipient first name', 'surname' => 'Recipient surname', 'account_holder_name' => 'GCash account holder name', 'mobile' => 'GCash mobile number (09…)', 'street' => 'Street address', 'city' => 'City', 'province' => 'Province', 'postal_code' => 'Postal code'] as $key => $label)<label>{{ $label }}<input name="{{ $key }}" required maxlength="{{ in_array($key, ['given_name','surname']) ? 50 : ($key === 'mobile' ? 11 : ($key === 'postal_code' ? 4 : 255)) }}" autocomplete="off"></label>@endforeach
@include('transactions.password-confirmation')<button class="button button-play">Request recipient review</button></form></details>@endif
</section>@endif
<h2>Payout history</h2><div class="review-list">@forelse($payouts as $payout)<div class="lesson-note"><b>PHP {{ \App\Support\ExactMoney::decimal($payout->gross_minor) }} · {{ $payout->state }}</b><p>Fee: PHP {{ \App\Support\ExactMoney::decimal($payout->fee_minor) }} &middot; Net amount: PHP {{ \App\Support\ExactMoney::decimal($payout->gross_minor - $payout->fee_minor) }}</p><p>{{ $payout->review_notes }} · {{ $payout->id }}</p></div>@empty<p>No payouts requested yet.</p>@endforelse</div>{{ $payouts->links() }}
<h2>Request a courtesy refund</h2><p class="field-hint">Unused access: seven days, before completing an assessment or confirming a coding attempt. Whole purchase: 14 days with its entire original lot unspent. Staff reviews each request; these limits do not waive statutory remedies.</p>
@foreach($accessSales as $sale)<details class="lesson-note"><summary>{{ ucfirst($sale->content_type) }} #{{ $sale->content_id }} · {{ $sale->kodebits }} KB</summary><form class="studio-form" method="post" action="{{ route('earnings.refund', ['Access', $sale->id]) }}">@csrf<label>Reason<textarea name="reason" required maxlength="500"></textarea></label>@include('transactions.password-confirmation')<button class="button button-dark">Request access refund</button></form></details>@endforeach
@if($ready)@foreach($topups as $topup)<details class="lesson-note"><summary>{{ ucfirst($topup->package) }} purchase · {{ $topup->id }}</summary><form class="studio-form" method="post" action="{{ route('earnings.refund', ['Purchase', $topup->id]) }}">@csrf<label>Reason<textarea name="reason" required maxlength="500"></textarea></label>@include('transactions.password-confirmation')<button class="button button-dark">Request unused purchase refund</button></form></details>@endforeach
@endif
<details class="lesson-note"><summary>Report a defect, dispute or exceptional remedy</summary><p>Requests outside courtesy limits receive a separate investigation. Include your transaction reference; do not include credentials or sensitive identity documents.</p><form class="studio-form" method="post" action="{{ route('earnings.remedy') }}">@csrf<label>Transaction reference<input name="reference" required maxlength="100"></label><label>What happened?<textarea name="message" required maxlength="1000"></textarea></label><button class="button button-dark">Request investigation</button></form></details>
<h2>Refund history</h2><div class="review-list">@forelse($refunds as $refund)<div class="lesson-note"><b>{{ $refund->kind }} · {{ $refund->state }}</b><p>{{ $refund->review_notes }} · {{ $refund->id }}</p></div>@empty<p>No refunds requested yet.</p>@endforelse</div>{{ $refunds->links() }}
@if($closure['wallet_kb'] > 0 || $closure['cash_minor'] > 0 || $closure['creator_kb_milli'] > 0)
<details class="lesson-note"><summary>Optional permanent balance relinquishment</summary><p>You can spend, claim, request a payout or seek a refund instead. Account deletion never forfeits balances automatically. If you choose this option, you permanently give up all the amounts below. Reserved balances and earnings still in their holding period must be settled first.</p>
<p><strong>{{ $closure['wallet_kb'] }} wallet KB · PHP {{ \App\Support\ExactMoney::decimal($closure['cash_minor']) }} cash earnings · {{ \App\Support\ExactMoney::milli($closure['creator_kb_milli']) }} earned KB</strong></p>
<form class="studio-form" method="post" action="{{ route('earnings.relinquish') }}">@csrf<input type="hidden" name="fingerprint" value="{{ $closure['fingerprint'] }}"><label>Type RELINQUISH MY BALANCES<input name="confirmation_phrase" required autocomplete="off" maxlength="50"></label>@include('transactions.password-confirmation')<button class="button button-danger">Permanently relinquish these balances</button></form></details>
@endif
@if(auth()->user()->account_role === \App\Enums\Role::Administrator)<p><a class="quiet-link" href="{{ route('finance.index') }}">Review financial requests</a></p>@endif
</section>
@endsection
