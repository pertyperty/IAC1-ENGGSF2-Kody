@extends('layouts.account')
@section('title', 'Account governance — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
<h1>Keep the community safe</h1><p>Browse current roles and account status, then open an account to review enforcement history.</p>
<form class="account-form governance-filters" method="GET" action="{{ route('account-governance.index') }}">
<label>Username<input name="q" maxlength="80" value="{{ $filters['q'] ?? '' }}"></label>
<label>Role<select name="role"><option value="">All roles</option>@foreach(\App\Enums\Role::cases() as $role)<option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->name }}</option>@endforeach</select></label>
<label>Status<select name="status"><option value="">All statuses</option>@foreach(\App\Enums\AccountStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
<div class="filter-actions"><button class="primary-button">Apply filters</button>@if(array_filter($filters))<a href="{{ route('account-governance.index') }}">Clear filters</a>@endif</div></form>
<div class="account-results" aria-label="Matching accounts">
@forelse($accounts as $account)<a class="account-result" href="{{ route('account-governance.show', $account) }}"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($account->username ?? 'K', 0, 1)) }}</span><span><strong>{{ $account->username ?? 'Account #'.$account->id }}</strong><span class="account-result-role">{{ $account->account_role->name }}</span></span><span class="status-pill">{{ $account->account_status->value }}</span><span aria-hidden="true">→</span></a>@empty<div class="empty-state"><h2>No accounts found</h2><p>Try another username or clear the role and status filters.</p></div>@endforelse
</div>
{{ $accounts->links() }}
@include('layouts.staff-tools')
@endsection
