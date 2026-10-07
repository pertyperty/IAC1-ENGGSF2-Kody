@extends('layouts.learning')
@section('title', $lesson['title'].' — Kody')
@section('content')
    <section class="module-play page-width"><a class="quiet-link" href="{{ route('dashboard') }}">← Your level ladder</a><div class="module-play-grid"><div><p class="overline">{{ $lesson['concept'] }} · BEGINNER</p><h1>{{ $lesson['title'] }}</h1><p class="hero-description">{{ $lesson['description'] }}</p><div class="lesson-note"><h2>Your playground</h2><p>Build a program, watch it run, then change it. Getting stuck is part of figuring it out. Use a hint whenever you like.</p><p>Clearing the game saves this level and unlocks the next. Your first verified game clear and first quiz win each earn 20 XP. This trail awards no KodeBits.</p></div>@include('learning.quiz')</div>@include('learning.game', ['trial' => false])</div></section>
@endsection
