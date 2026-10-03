@extends('layouts.account')
@section('title', 'Edit your profile — Kody')
@section('content')
<p class="eyebrow">MAKE IT YOURS</p><h1>Your player profile</h1>
<p class="intro">Update your name and username. Changing your email or password requires your current password and ends your signed-in sessions.</p>
@if($errors->any())<div class="notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="account-form" method="POST" action="{{ route('account.update') }}">@csrf @method('PATCH')
<input type="hidden" name="profile_version" value="{{ $profile['profile_version'] }}">
<label for="username">Username</label><input id="username" name="username" value="{{ old('username', $profile['username']) }}" minlength="6" maxlength="30" autocomplete="username" required>
<label for="first_name">First name</label><input id="first_name" name="first_name" value="{{ old('first_name', $profile['first_name']) }}" maxlength="50" autocomplete="given-name" required>
<label for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ old('last_name', $profile['last_name']) }}" maxlength="50" autocomplete="family-name" required>
<label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $profile['email']) }}" maxlength="100" autocomplete="email" required>
<p>Changing your email pauses account access until you verify the new address.</p>
<label for="current_password">Current password (for email/password changes)</label><input id="current_password" type="password" name="current_password" maxlength="1024" autocomplete="current-password">
<label for="password">New password (optional)</label><input id="password" type="password" name="password" minlength="12" maxlength="32" autocomplete="new-password">
<p>12–32 characters with uppercase, lowercase, a number and a symbol.</p>
<label for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" maxlength="32" autocomplete="new-password">
<button class="primary-button" type="submit">Save your profile</button></form>
<p class="secondary-link"><a href="{{ route('account.show') }}">Back to your profile</a></p>
@endsection
