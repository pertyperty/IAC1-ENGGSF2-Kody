@extends('layouts.learning')
@section('title', 'Practice - Kody')
@section('content')
<section class="page-width arcade-page" data-focus-default>
    <header class="practice-header">
        <div class="practice-header-main">
            <h1>{{ $game['title'] }}</h1>
            @if($game['template'] !== 'choice-quiz')
                <nav class="practice-levels" aria-label="Practice difficulty">
                    @foreach($levels as $value => $label)<a href="{{ route('arcade', ['game' => $selected, 'level' => $value]) }}" @if($level === $value) aria-current="page" @endif>{{ $label }}</a>@endforeach
                </nav>
            @endif
        </div>
        <details class="practice-options">
            <summary>Change activity</summary>
            <div class="practice-drawer">
                <nav class="practice-activity-grid" aria-label="Practice activities">
                    @foreach($cabinets as $slug => $instance)
                        @php
                            $iconMap = ['pixel-studio' => 'panel', 'number-machine' => 'chart', 'sort-lab' => 'updates', 'terminal-quest' => 'code', 'logic-switchboard' => 'spark', 'string-forge' => 'pencil', 'memory-trail' => 'book', 'sequences' => 'journey', 'loops' => 'refresh', 'conditions' => 'crystal'];
                            $icon = $iconMap[$slug] ?? 'game';
                        @endphp
                        <a class="practice-activity-card" href="{{ route('arcade', ['game' => $slug, 'level' => $level]) }}" @if($selected === $slug) aria-current="page" @endif>
                            <span class="practice-card-icon" aria-hidden="true"><x-action-icon :name="$icon"/></span>
                            <span class="practice-card-copy">
                                <strong>{{ $instance['title'] }}</strong>
                                <small>{{ $instance['concept'] }}</small>
                            </span>
                        </a>
                    @endforeach
                </nav>
                <form method="get" action="{{ route('arcade') }}" class="practice-picker" data-practice-picker>
                    <input type="hidden" name="level" value="{{ $level }}">
                    <label for="practice-game">Choose a concept</label>
                    <select id="practice-game" name="game">
                        @foreach($cabinets as $slug => $instance)
                            <option value="{{ $slug }}" @selected($selected === $slug)>{{ $instance['concept'] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="button button-secondary">Open activity</button>
                </form>
            </div>
        </details>
    </header>
    <section id="arcade-station" class="arcade-station" aria-label="Selected practice game" data-activity-title-shown>
        @if($game['template'] === 'choice-quiz')@include('learning.quiz', ['quiz' => $game, 'module' => null])@elseif($game['template'] === 'command-garden')@include('learning.game', ['game' => $game, 'completionUrl' => null])@else @include('learning.arcade-game', ['game' => $game, 'completionUrl' => null])@endif
    </section>
    @if($game['template'] !== 'choice-quiz' && $level !== 'stretch')
    <a class="button button-play practice-next" data-practice-next hidden href="{{ route('arcade', ['game' => $selected, 'level' => $level === 'warmup' ? 'challenge' : 'stretch']) }}">Try the {{ $level === 'warmup' ? 'challenge' : 'stretch task' }}</a>
    @endif
    <div class="practice-footer"><p>Practice here is free. Account progress is saved in modules and the Tower.</p><a href="{{ route('learning.catalog', ['template' => $game['template']]) }}">Find modules on this concept</a></div>
</section>
@endsection
