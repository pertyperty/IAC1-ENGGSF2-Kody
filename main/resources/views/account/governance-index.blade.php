@extends('layouts.account')
@section('title', 'Account governance — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<h1>Keep the community safe</h1><p>Browse current roles and account status, then open an account to review enforcement history.</p>
<form class="account-form" method="GET" action="{{ route('account-governance.index') }}">
<label>Username<input name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}"></label>
<label>Role<select name="role"><option value="">All roles</option>@foreach(\App\Enums\Role::cases() as $role)<option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->name }}</option>@endforeach</select></label>
<label>Status<select name="status"><option value="">All statuses</option>@foreach(\App\Enums\AccountStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
<button class="primary-button">Apply filters</button></form>
@forelse($accounts as $account)<p><a href="{{ route('account-governance.show', $account) }}">{{ $account->username ?? 'Account #'.$account->id }}</a> · {{ $account->account_role->name }} · {{ $account->account_status->value }}</p>@empty<p>No matching accounts.</p>@endforelse
{{ $accounts->links() }}
<p class="secondary-link"><a href="{{ route('content-moderation.index') }}">Staff content moderation</a></p>
@can('viewAny', \App\Models\GamePreset::class)<p class="secondary-link"><a href="{{ route('game-presets.index') }}">Game preset workshop</a></p>@endcan
@can('viewAny', \App\Models\FaqEntry::class)<p class="secondary-link"><a href="{{ route('faq-management.index') }}">Help workshop</a></p>@endcan
@can('viewReports', \App\Models\User::class)<p class="secondary-link"><a href="{{ route('system-reports') }}">View system reports</a></p>@endcan
@endsection
