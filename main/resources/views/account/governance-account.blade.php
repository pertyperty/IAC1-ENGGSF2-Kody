@extends('layouts.account')
@section('title', 'Account enforcement — Kody')
@section('content')
<h1>{{ $account->username ?? 'Account #'.$account->id }}</h1><p>{{ $account->account_role->name }} · {{ $account->account_status->value }}</p>
@if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@can('manage', $account)
@if(in_array($account->account_status, [\App\Enums\AccountStatus::Active, \App\Enums\AccountStatus::Suspended], true))
<form class="account-form" method="POST" action="{{ route('account-governance.enforce', $account) }}">@csrf
<input type="hidden" name="profile_version" value="{{ $account->profile_version }}">
<input type="hidden" name="action" value="{{ $account->account_status === \App\Enums\AccountStatus::Active ? 'Suspended' : 'Reinstated' }}">
@if($account->account_status === \App\Enums\AccountStatus::Active)<p>Suspension ends current sessions and restricts access immediately. Profile, learning records, content and committed coding evaluations remain intact.</p>@else<p>Reinstatement returns the account to Active. Previous sessions stay revoked; the account holder must sign in again.</p>@endif
<label><input type="checkbox" name="confirmed" value="1" required> I confirm this account enforcement action.</label>
<button class="primary-button">{{ $account->account_status === \App\Enums\AccountStatus::Active ? 'Confirm suspension' : 'Confirm reinstatement' }}</button></form>
@endif
@endcan
<h2>Enforcement history</h2>@forelse($history as $event)<p>{{ $event->action }} · Staff account #{{ $event->actor_id }} · {{ \Carbon\CarbonImmutable::parse($event->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@empty<p>No recorded enforcement actions.</p>@endforelse
{{ $history->links() }}<p class="secondary-link"><a href="{{ route('account-governance.index') }}">Back to accounts</a></p>
@endsection
