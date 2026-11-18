@extends('layouts.learning')
@section('title', 'Practice - Kody')
@section('content')
<section class="page-width arcade-page">
    <header class="practice-header"><div><h1>Practice</h1><p>Solve a task, then check the concept behind it.</p></div>
        <form method="get" action="{{ route('arcade') }}" class="practice-picker" data-practice-picker><label for="practice-game">Choose a concept</label><select id="practice-game" name="game">@foreach($cabinets as $slug => $instance)<option value="{{ $slug }}" @selected($selected === $slug)>{{ $instance['concept'] }}</option>@endforeach</select><button type="submit" class="button button-secondary">Open activity</button></form>
    </header>
    <section id="arcade-station" class="arcade-station" aria-label="Selected practice game">
        @if($game['template'] === 'choice-quiz')@include('learning.quiz', ['quiz' => $game, 'module' => null])@else @include('learning.arcade-game', ['game' => $game, 'completionUrl' => null])@endif
    </section>
    <div class="practice-footer"><p>Practice here is free. Account progress is saved in lessons and the Tower.</p><a href="{{ route('learning.catalog', ['template' => $game['template']]) }}">Find lessons on this concept</a></div>
</section>
@endsection
