@extends('layouts.account')
@section('title', 'Create your account — Kody')
@section('content')
    <p class="eyebrow">YOUR LEARNING JOURNEY</p>
    <h1>Create your account</h1>
    <p class="intro">Join Kody and take your next step in programming.</p>
    @if ($errors->any())
        <div class="form-errors" role="alert"><p>Please check your details.</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form action="{{ route('register.store') }}" method="post" enctype="multipart/form-data" class="account-form">
        @csrf
        <div class="form-row">
            <label>First name<input name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" maxlength="50" required></label>
            <label>Last name<input name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" maxlength="50" required></label>
        </div>
        <label>Username<input name="username" value="{{ old('username') }}" autocomplete="username" minlength="6" maxlength="30" required><small>6–30 characters. Choose a unique username.</small></label>
        <label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="100" required></label>
        <x-password-field name="password" label="Password" :minlength="12" hint="12–32 characters, including uppercase, lowercase, a number and a symbol." />
        <x-password-field name="password_confirmation" label="Confirm password" :minlength="12" />
        <label>I’m joining as<select name="account_type" id="account-type"><option value="learner" @selected(old('account_type', 'learner') === 'learner')>Learner</option><option value="instructor" @selected(old('account_type') === 'instructor')>Instructor applicant</option></select></label>
        <fieldset id="instructor-fields">
            <legend>Instructor application</legend>
            <p>You’ll start with Learner access. Teaching credentials require review before Instructor access is granted.</p>
            <label>Institution<input name="institution_name" value="{{ old('institution_name') }}" maxlength="100"></label>
            <label>Specialization<input name="specialization" value="{{ old('specialization') }}" maxlength="100"></label>
            <label>Teaching credentials<input type="file" name="credential_document" accept=".pdf,.png,.jpg,.jpeg"><small>PDF, PNG or JPEG; up to {{ number_format(config('account.credentials.max_kilobytes') / 1024, 1) }} MB. Stored privately.</small></label>
        </fieldset>
        <button type="submit" class="primary-button">Create account <span aria-hidden="true">↗</span></button>
        <p class="form-note">We’ll email you a verification link before you can sign in.</p>
    </form>
    <p class="secondary-link"><a href="{{ route('verification.notice') }}">Already registered? Request a verification link.</a></p>
    <p class="secondary-link"><a href="{{ route('login') }}">Already verified? Sign in.</a></p>
@endsection
