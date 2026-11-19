@extends('layouts.base')
@section('page-class', 'learning-page')
@section('footer')
<footer class="site-footer">@include('layouts.brand')<a href="{{ route('help.index') }}">Help center</a><a href="{{ route('learning.catalog') }}">Browse lessons</a><a href="{{ route('legal.terms') }}">Terms</a><a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('contact') }}"><x-action-icon name="mail"/> Contact us</a></footer>
@endsection
