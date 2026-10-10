@extends('layouts.base')
@section('page-class', 'learning-page')
@section('footer')
<footer class="site-footer">@include('layouts.brand')<p>Little steps. Big ideas. Made for curious minds.</p><a href="{{ route('help.index') }}">A little help</a><a href="{{ route('learning.catalog') }}">Find your next adventure ↗︎</a></footer>
@endsection
