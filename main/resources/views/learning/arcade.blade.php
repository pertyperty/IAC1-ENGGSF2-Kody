@extends('layouts.learning')
@section('title', 'The playground — Kody')
@section('content')
<section class="review-page page-width"><p class="overline">FOUR NEW WAYS TO THINK IN CODE</p><h1>Pick your next little adventure.</h1><p>Paint pixels, transform numbers, sort a list or explore a terminal. These guest trials are practice; published creator modules save validated wins.</p>
<nav class="arcade-tabs" aria-label="Choose a game">@foreach(config('arcade') as $slug => $instance)<a class="button {{ $selected === $slug ? 'button-play' : 'button-dark' }}" href="{{ route('arcade', ['game' => $slug]) }}" @if($selected === $slug) aria-current="page" @endif>{{ $instance['title'] }}</a>@endforeach</nav>
@include('learning.arcade-game', ['game' => $game, 'completionUrl' => null])
<p><a class="quiet-link" href="{{ route('learning.catalog', ['template' => $selected]) }}">Find lessons with this game →</a></p></section>
@endsection
