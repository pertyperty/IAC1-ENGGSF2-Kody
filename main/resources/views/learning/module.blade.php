@extends('layouts.learning')
@section('title', $lesson['title'].' — Kody')
@section('content')
    <section class="module-play page-width"><a class="quiet-link" href="{{ route('learning.catalog') }}">← All adventures</a><div class="module-play-grid"><div><p class="overline">{{ $lesson['concept'] }} · BEGINNER</p><h1>{{ $lesson['title'] }}</h1><p class="hero-description">{{ $lesson['description'] }}</p><div class="lesson-note"><h2>Your playground</h2><p>Build a program, watch it run, then change it. Getting stuck is part of figuring it out. Use a hint whenever you like.</p><p>This practice game doesn’t award KodeBits or save course progress yet.</p></div>@include('learning.quiz')</div>@include('learning.game', ['trial' => false])</div></section>
@endsection
