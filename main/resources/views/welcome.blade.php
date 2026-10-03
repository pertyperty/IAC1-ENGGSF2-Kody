@extends('layouts.account')

@section('title', 'Kody — Learn programming')

@section('content')
    <section class="account-card">
        <p class="eyebrow">WELCOME TO KODY</p>
        <h1>Your next programming milestone starts here.</h1>
        <p class="intro">Create your account to begin your learning journey.</p>
        <p><a href="{{ route('register') }}">Create an account</a></p>
        <p><a href="{{ route('login') }}">Sign in</a></p>
        <p><a href="{{ route('verification.notice') }}">Verify your email</a></p>
    </section>
@endsection
