@extends('layouts.learning')
@section('title')@yield('code') · Kody @endsection
@section('content')
<section class="kody-error"><x-kody-art name="thinking" size="160"/><p class="overline"><x-kody-art name="error" size="28"/> KODY · @yield('code')</p><h1>@yield('message')</h1><p>Let’s find a comfortable way back. Your saved progress stays with your account.</p><div class="result-actions"><a class="button button-play" href="{{ url('/') }}">Back to Kody</a><a class="button button-secondary" href="{{ url('/help') }}">Find help</a></div></section>
@endsection
