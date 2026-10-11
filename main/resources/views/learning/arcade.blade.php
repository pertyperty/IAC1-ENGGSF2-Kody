@extends('layouts.learning')
@section('title', 'Practice Arcade — Kody')
@section('content')
<section class="page-width arcade-page">
<header class="arcade-lobby-header"><div><p class="overline">PICK A CABINET. DISCOVER AN IDEA.</p><h1>Practice Arcade<span class="brand-dot">.</span></h1><p>Small experiments. Unlimited retries. Step into a game and make something happen.</p><span class="step-pill"><x-action-icon name="game"/> Free practice · no attempts used</span></div><x-kody-art size="140"/></header>
<nav class="arcade-cabinets" aria-label="Choose a game">@foreach($cabinets as $slug => $instance)<a class="arcade-cabinet cabinet-{{ $slug }}" href="{{ route('arcade', ['game' => $slug]) }}#arcade-station" @if($selected === $slug) aria-current="page" @endif><span class="cabinet-screen" aria-hidden="true"><x-arcade-cabinet :template="$slug"/></span><span class="cabinet-copy"><b>{{ $instance['title'] }}</b><span>{{ $instance['concept'] }}</span></span><span class="cabinet-action"><x-action-icon :name="$selected === $slug ? 'check' : 'game'"/>{{ $selected === $slug ? 'Now playing' : 'Play this game' }}</span></a>@endforeach</nav>
<section id="arcade-station" class="arcade-station" aria-label="Selected practice game"><div class="panel-heading"><div><p class="overline">YOUR PRACTICE STATION</p><p class="field-hint">Local practice has no XP or saved completion. Creator lessons validate and save eligible wins.</p></div><a class="button button-secondary button-small" href="#main-content"><x-action-icon name="menu"/> Game lobby</a></div>
@if($game['template'] === 'choice-quiz')@include('learning.quiz', ['quiz' => $game, 'module' => null])@else @include('learning.arcade-game', ['game' => $game, 'completionUrl' => null])@endif
<p><a class="button button-secondary" href="{{ route('learning.catalog', ['template' => $game['template']]) }}"><x-action-icon name="book"/> Find lessons with this game</a></p></section></section>
@endsection
