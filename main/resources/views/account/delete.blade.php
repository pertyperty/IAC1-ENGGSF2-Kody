@extends('layouts.account')
@section('title', 'Delete your account — Kody')
@section('content')
<p class="eyebrow">A PERMANENT GOODBYE</p><h1>Delete your account</h1>
<p class="intro">This permanently removes your profile identity, private learning records and submissions. You cannot sign in or recover this account afterward. Private credential files are removed by background cleanup; minimal audit references remain.</p>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($authored)<p class="notice">Deletion for accounts with authored modules, courses or challenges is unavailable until content retention rules are defined. You can archive your account to take a break.</p>
@else
<form class="account-form" method="POST" action="{{ route('account.delete.store') }}">@csrf
<input type="hidden" name="profile_version" value="{{ $version }}">
<label>Type DELETE MY ACCOUNT<input name="confirmation_phrase" autocomplete="off" required></label>
<label>Current password<input type="password" name="current_password" maxlength="1024" autocomplete="current-password" required></label>
<label><input type="checkbox" name="confirmed" value="1" required> I understand this is permanent and want to delete my account.</label>
<p>Any active coding evaluation must finish first.</p><button class="primary-button" type="submit">Permanently delete my account</button></form>
@endif
<p class="secondary-link"><a href="{{ route('account.show') }}">Cancel and keep your account</a></p>
<p class="secondary-link"><a href="{{ route('account.archive') }}">Take a break with account archival</a></p>
@endsection
