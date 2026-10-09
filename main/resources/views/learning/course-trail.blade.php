<ol class="course-trail" aria-label="Course adventure trail">
    @foreach($trail['steps'] as $step)
    <li class="trail-step {{ $step['state'] === 'Ready' ? 'trail-ready' : '' }} {{ $step['completed'] ? 'trail-cleared' : '' }}">
        <span class="trail-marker" aria-hidden="true">{{ $step['completed'] ? '✓' : $step['position'] }}</span>
        <div class="trail-copy"><span class="trail-state">{{ $step['state'] }} · {{ $step['kind'] }}</span><h3>{{ $step['title'] }}</h3>
            @if($step['state'] === 'Locked')<p>Locked · Complete the previous adventures first.</p>@elseif($step['state'] === 'Unavailable')<p>This adventure is currently unavailable. Your saved progress is retained.</p>@elseif($step['state'] === 'Join to play')<p>Join to explore this adventure.</p>@elseif($step['state'] === 'Ready' && $step['visited'])<p>Continue your adventure</p>@endif
        </div>
        @if($step['url'])<a class="button {{ $step['completed'] ? 'button-secondary' : 'button-play' }} button-small" href="{{ $step['url'] }}" aria-label="{{ $step['completed'] ? 'Revisit' : 'Play' }} {{ $step['title'] }}">{{ $step['completed'] ? 'Revisit' : 'Let’s play' }} →</a>@endif
    </li>
    @endforeach
</ol>
