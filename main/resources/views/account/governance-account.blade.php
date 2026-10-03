@extends('layouts.account')
@section('title', 'Account governance — Kody')
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
@can('changeModeratorRole', $account)
<h2>{{ $account->account_role === \App\Enums\Role::Moderator ? 'Remove Moderator appointment' : 'Appoint a Moderator' }}</h2>
<form class="account-form" method="POST" action="{{ route('account-governance.moderator', $account) }}">@csrf
<input type="hidden" name="profile_version" value="{{ $account->profile_version }}">
<input type="hidden" name="action" value="{{ $account->account_role === \App\Enums\Role::Moderator ? 'Removed' : 'Appointed' }}">
<p>{{ $account->account_role === \App\Enums\Role::Moderator ? 'Removal returns this account to '.$account->moderator_prior_role->value.'.' : 'Appointment changes this account’s current role to Moderator and records its prior role for later removal.' }} Existing content, learning records and applications remain intact. Available tools follow the current role.</p>
<p>This ends the account holder’s sessions. They must sign in again.</p>
<label>Your current Administrator password<input type="password" name="current_password" maxlength="1024" autocomplete="current-password" required></label>
<label><input type="checkbox" name="confirmed" value="1" required> I confirm this Moderator role action.</label><button class="primary-button">Confirm role action</button></form>
@endcan
<h2>Moderator appointment history</h2>@forelse($roleHistory as $change)<p>{{ $change->action }} · {{ $change->previous_role }} → {{ $change->resulting_role }} · Staff account #{{ $change->actor_id }} · {{ \Carbon\CarbonImmutable::parse($change->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@empty<p>No recorded Moderator appointments.</p>@endforelse
{{ $roleHistory->links() }}
@can('correctProfile', $account)
<h2>Help with profile details</h2>
<form class="account-form" method="POST" action="{{ route('account-governance.profile', $account) }}">@csrf @method('PATCH')
<input type="hidden" name="profile_version" value="{{ $account->profile_version }}">
<p>Correct these details only when the account holder requests help. Saving a correction ends their sessions and sends a notice.</p>
<label>Username<input name="username" value="{{ old('username', $account->username) }}" minlength="6" maxlength="30" required></label>
<label>First name<input name="first_name" value="{{ old('first_name', $account->first_name) }}" maxlength="50" required></label>
<label>Last name<input name="last_name" value="{{ old('last_name', $account->last_name) }}" maxlength="50" required></label>
<label>Your current Administrator password<input type="password" name="current_password" maxlength="1024" autocomplete="current-password" required></label>
<label><input type="checkbox" name="support_requested" value="1" required> The account holder requested help correcting these details.</label>
<label><input type="checkbox" name="confirmed" value="1" required> I confirm this profile correction.</label>
<button class="primary-button">Save support correction</button></form>
@endcan
<h2>Support correction history</h2>@forelse($supportHistory as $correction)<p>Profile correction · Staff account #{{ $correction->actor_id }} · {{ \Carbon\CarbonImmutable::parse($correction->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@empty<p>No recorded support corrections.</p>@endforelse
{{ $supportHistory->links() }}
<h2>Enforcement history</h2>@forelse($history as $event)<p>{{ $event->action }} · Staff account #{{ $event->actor_id }} · {{ \Carbon\CarbonImmutable::parse($event->created_at)->setTimezone('Asia/Manila')->format('M j, Y g:i A') }} Asia/Manila</p>@empty<p>No recorded enforcement actions.</p>@endforelse
{{ $history->links() }}<p class="secondary-link"><a href="{{ route('account-governance.index') }}">Back to accounts</a></p>
@endsection
