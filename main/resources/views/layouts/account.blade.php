@extends('layouts.base')
@section('page-class', 'account-page')
@section('main-class')account-shell @yield('shell-class')@endsection
@section('footer')
<footer class="account-footer"><a href="{{ route('legal.terms') }}">Terms</a> <a href="{{ route('legal.privacy') }}">Privacy</a> <a href="{{ route('contact') }}">Contact</a></footer>
@endsection
