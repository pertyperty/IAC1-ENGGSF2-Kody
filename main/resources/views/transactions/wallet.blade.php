@extends('layouts.learning')
@section('title', 'Your wallet — Kody')
@section('content')
<section class="review-page page-width wallet-page">
    <p class="overline">YOUR KODY WALLET</p><h1>More adventures ahead</h1>
    @include('layouts.form-errors')
    <div class="wallet-summary">
        <article class="wallet-summary-card"><h2>Available KodeBits</h2><div class="wallet-amount"><x-kody-art name="kodebit" size="64"/><strong>{{ number_format($wallet['available']) }} KB</strong></div><p>{{ number_format($wallet['reserved']) }} KB reserved · {{ number_format($wallet['balance']) }} KB total</p></article>
        <article class="wallet-summary-card">
            @if(in_array(auth()->user()->account_role, [\App\Enums\Role::Learner, \App\Enums\Role::Contributor, \App\Enums\Role::Instructor], true))
                <h2>Your learning rank</h2><div class="wallet-amount"><x-kody-art name="rank" size="48"/><strong>{{ $rank['rank'] }}</strong></div><p>{{ number_format($rank['xp']) }} XP earned through learning. Purchases grant no XP.</p>
            @else<h2>Your personal ledger</h2><p>These balances belong to your account. Platform accounting remains in the authorized Finance workspace.</p>@endif
        </article>
    </div>
    <div class="workspace-columns wallet-columns">
        <section class="content-panel">
            <h2>Choose your next boost</h2><p class="field-hint">One-time PHP purchases. KodeBits do not expire and cannot be exchanged for cash.</p>
            @if(!$paymentsReady)<p class="notice"><x-action-icon name="shield"/> Live checkout is being prepared. Free adventures remain available.</p>@endif
            <div class="wallet-packages">
                @foreach(config('economy.packages') as $slug => $package)
                    <article class="wallet-package"><x-kody-art name="kodebit" class="package-art" size="64"/><h3>{{ $package['label'] }}</h3><strong>{{ number_format($package['kodebits']) }} KodeBits</strong><p>PHP {{ \App\Support\ExactMoney::decimal($package['price_minor']) }}</p>
                        @if($paymentsReady)<form class="studio-form" method="post" action="{{ route('wallet.purchase') }}">@csrf<input type="hidden" name="package" value="{{ $slug }}"><input type="hidden" name="confirmation_id" value="{{ $confirmationId }}">@include('transactions.password-confirmation')<button class="button button-play">Continue to Xendit <x-action-icon name="right"/></button></form>
                        @else<span class="field-hint">Checkout setup pending</span>@endif
                    </article>
                @endforeach
            </div>
            @if($purchases->isNotEmpty())
                <h2>Purchase status</h2><div class="wallet-history">
                @foreach($purchases as $purchase)
                    <article class="wallet-transaction"><strong>{{ ucfirst($purchase->package) }}</strong><span class="status-pill">{{ $purchase->state }}</span><p>PHP {{ \App\Support\ExactMoney::decimal($purchase->gross_minor) }}</p><a class="quiet-link" href="{{ route('wallet.purchase.show', $purchase->id) }}">View status <x-action-icon name="right"/></a>
                    @if($purchase->lot_id)<a class="quiet-link" href="{{ route('wallet.receipt', $purchase->id) }}">Payment receipt <x-action-icon name="download"/></a>@endif
                    <p class="transaction-reference">{{ $purchase->id }}</p></article>
                @endforeach
                </div>
            @endif
        </section>
        <section class="content-panel"><h2>KodeBit history</h2><div class="wallet-history">
            @forelse($operations as $operation)
                <article class="wallet-transaction"><strong>{{ $operation->kind }}</strong><b class="{{ $operation->kodebits > 0 ? 'transaction-credit' : 'transaction-debit' }}">{{ $operation->kodebits > 0 ? '+' : '' }}{{ number_format($operation->kodebits) }} KB</b><p>{{ \Carbon\CarbonImmutable::parse($operation->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p><p class="transaction-reference">{{ $operation->id }}</p></article>
            @empty<x-empty-state title="Your next adventure starts here" description="Verified purchases, learning access and rewards will appear in this ledger." />@endforelse
        </div>{{ $operations->links() }}</section>
    </div>
    @if(in_array(auth()->user()->account_role, [\App\Enums\Role::Instructor, \App\Enums\Role::Contributor], true) || $earnings->isNotEmpty())
    <section class="content-panel"><h2>Your creator earnings</h2><p class="field-hint">Sales mature after 14 days. Instructor earnings settle in PHP; Contributor earnings settle in whole KodeBits.</p>
        <div class="wallet-history">@forelse($earnings as $earning)<article class="wallet-transaction"><strong>{{ $earning->kind === 'Cash' ? 'PHP '.\App\Support\ExactMoney::decimal($earning->amount_minor - $earning->claimed_minor) : \App\Support\ExactMoney::milli($earning->kb_milli - $earning->claimed_kb_milli).' KB' }}</strong><span class="status-pill">{{ $earning->status }}</span><p>Available {{ \Carbon\CarbonImmutable::parse($earning->available_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p></article>@empty<p>Your paid-content sales will appear here.</p>@endforelse</div>{{ $earnings->links() }}
    </section>
    @endif
    <div class="studio-actions"><a class="button button-secondary" href="{{ route('earnings.index') }}"><x-action-icon name="wallet"/> Manage refunds and earnings</a></div>
</section>
@endsection
