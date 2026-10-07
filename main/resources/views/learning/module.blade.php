@extends('layouts.learning')
@section('title', $lesson['title'].' — Kody')
@section('content')
<section class="module-play page-width play-stage">
    <div class="lesson-breadcrumb"><a class="quiet-link" href="{{ route('dashboard') }}">← Your level ladder</a><span class="step-pill">{{ $lesson['concept'] }} · Beginner</span></div>
    <div class="play-stage-heading"><div><h1>{{ $lesson['title'] }}</h1><p>{{ $lesson['description'] }}</p></div><span class="stage-marker"><span aria-hidden="true">✦</span> Learn by doing</span></div>
    @include('learning.game', ['trial' => false])
    <div class="lesson-support-grid">
        <div class="lesson-companion"><p class="overline">THE IDEA BEHIND THE PLAY</p><h2>Try. Tweak. Try again.</h2><p>Build a program and watch each instruction light up as your explorer moves. Select any step to rearrange it. A wrong turn is a chance to change one small thing.</p><details><summary>How your progress is saved</summary><p>Clearing the game saves this level and unlocks the next. Your first verified game clear and first quiz win each earn 20 XP. This trail awards no KodeBits.</p></details><a class="quiet-link" href="{{ route('arcade') }}">Explore other ways to play →</a></div>
        @include('learning.quiz')
    </div>
</section>
@endsection
