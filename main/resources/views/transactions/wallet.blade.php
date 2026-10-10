@extends('layouts.learning')
@section('title', 'Your wallet — Kody')
@section('content')
<section class="review-page page-width"><p class="overline">YOUR KODY WALLET</p><h1>More adventures ahead.</h1>

@if($errors->any())<p class="studio-errors" role="alert">{{ $errors->first() }}</p>@endif
<div class="dashboard-grid"><article class="lesson-note"><h2>{{ number_format($wallet['available']) }} KB available</h2><p>{{ $wallet['reserved'] }} KB reserved · {{ $wallet['balance'] }} KB total</p></article><article class="lesson-note"><h2>{{ $rank['rank'] }}</h2><p>{{ number_format($rank['xp']) }} XP earned through validated learning. Purchasing KodeBits grants no XP.</p></article></div>
<h2>Choose your next boost</h2><p class="field-hint">One-time PHP purchases. KodeBits do not expire and cannot be exchanged for cash.</p>
@if(!$paymentsReady)<p class="lesson-note">Payments are being prepared. You can keep playing free adventures.</p>@endif
<div class="dashboard-grid">@foreach(config('economy.packages') as $slug => $package)<article class="lesson-note"><h3>{{ $package['label'] }}</h3><b>{{ $package['kodebits'] }} KB · PHP {{ \App\Support\ExactMoney::decimal($package['price_minor']) }}</b>
@if($paymentsReady)<form class="studio-form" method="post" action="{{ route('wallet.purchase') }}">@csrf<input type="hidden" name="package" value="{{ $slug }}"><input type="hidden" name="confirmation_id" value="{{ $confirmationId }}">@include('transactions.password-confirmation')<button class="button button-play">Continue to GCash →</button></form>@endif</article>@endforeach</div>
@if($purchases->isNotEmpty())<h2>Purchase status</h2><div class="review-list">@foreach($purchases as $purchase)<div class="lesson-note"><b>{{ ucfirst($purchase->package) }} · {{ $purchase->state }}</b><p>PHP {{ \App\Support\ExactMoney::decimal($purchase->gross_minor) }} · {{ $purchase->id }}</p>@if($purchase->state === 'Pending' && $purchase->checkout_url)<a class="quiet-link" href="{{ route('wallet.checkout', $purchase->id) }}">Continue secure payment →</a>@endif</div>@endforeach</div>@endif
<h2>KodeBit history</h2><div class="review-list">@forelse($operations as $operation)<div class="lesson-note"><b>{{ $operation->kind }} · {{ $operation->kodebits > 0 ? '+' : '' }}{{ $operation->kodebits }} KB</b><p>{{ $operation->created_at }} · {{ $operation->id }}</p></div>@empty<p>Your validated purchases and learning access will appear here.</p>@endforelse</div>{{ $operations->links() }}
<h2>Your creator earnings</h2><p class="field-hint">Sales mature after 14 days. Instructor earnings settle in PHP; Contributor earnings settle in whole KodeBits.</p>
<div class="review-list">@forelse($earnings as $earning)<div class="lesson-note"><b>{{ $earning->kind === 'Cash' ? 'PHP '.\App\Support\ExactMoney::decimal($earning->amount_minor - $earning->claimed_minor) : \App\Support\ExactMoney::milli($earning->kb_milli - $earning->claimed_kb_milli).' KB' }} · {{ $earning->status }}</b><p>Available {{ $earning->available_at }} · {{ $earning->id }}</p></div>@empty<p>Your paid-content sales will appear here.</p>@endforelse</div>{{ $earnings->links() }}
<p><a class="quiet-link" href="{{ route('earnings.index') }}">Manage earnings and refunds →</a></p>
</section>
@endsection
