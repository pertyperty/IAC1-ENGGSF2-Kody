@extends('layouts.base')
@section('page-class', 'learning-page')
@section('footer')
<footer class="site-footer"><a href="{{ route('home') }}" class="play-brand">kody<span class="brand-dot">.</span></a><p>Little steps. Big ideas. Made for curious minds.</p><a href="{{ route('help.index') }}">A little help</a><a href="{{ route('learning.catalog') }}">Find your next adventure ↗︎</a></footer>
@endsection
