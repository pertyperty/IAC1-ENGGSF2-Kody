@extends('layouts.account')
@section('title', 'Take a break — Kody')
@section('content')
<p class="eyebrow">YOUR JOURNEY CAN WAIT</p><h1>Take a break from Kody</h1>
<p class="intro">Archiving ends your signed-in sessions and prevents sign-in. Your account data stays intact. Use account recovery and choose a new password to return.</p>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="account-form" method="POST" action="{{ route('account.archive.store') }}">@csrf
<input type="hidden" name="profile_version" value="{{ $version }}">
<label>Current password<input type="password" name="current_password" maxlength="1024" autocomplete="current-password" required></label>
<label><input type="checkbox" name="confirmed" value="1" required> I understand and want to archive my account.</label>
<button class="primary-button" type="submit">Archive my account</button></form>
<p class="secondary-link"><a href="{{ route('account.show') }}">Cancel and keep learning</a></p>
@endsection
