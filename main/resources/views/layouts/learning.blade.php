@extends('layouts.base')
@section('page-class', 'learning-page')
@section('footer')
<footer class="site-footer">@include('layouts.brand')<p>Learn programming through practice.</p><a href="{{ route('help.index') }}">Help center</a><a href="{{ route('learning.catalog') }}">Browse lessons <x-action-icon name="right"/></a><a href="{{ route('contact') }}"><x-action-icon name="mail"/> Contact us</a></footer>
@endsection
