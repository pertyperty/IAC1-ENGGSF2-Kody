<ol class="course-trail" aria-label="Course lessons">
    @foreach($trail['steps'] as $step)
    <li class="trail-step {{ $step['state'] === 'Ready' ? 'trail-ready' : '' }} {{ $step['completed'] ? 'trail-cleared' : '' }}">
        <span class="trail-marker" aria-hidden="true">@if($step['completed'])<x-action-icon name="check"/>@else{{ $step['position'] }}@endif</span>
        <div class="trail-copy"><span class="trail-state">{{ $step['state'] }} · {{ $step['kind'] }}</span><h3>{{ $step['title'] }}</h3>
            @if($step['state'] === 'Locked')<p>Locked · Complete the previous lessons first.</p>@elseif($step['state'] === 'Unavailable')<p>This lesson is currently unavailable. Your saved progress is retained.</p>@elseif($step['state'] === 'Join to play')<p>Enroll to open this lesson.</p>@elseif($step['state'] === 'Ready' && $step['visited'])<p>Continue lesson</p>@endif
        </div>
        @if($step['url'])<a class="button {{ $step['completed'] ? 'button-secondary' : 'button-play' }} button-small" href="{{ $step['url'] }}" aria-label="{{ $step['completed'] ? 'Revisit' : 'Open lesson:' }} {{ $step['title'] }}">{{ $step['completed'] ? 'Revisit' : 'Open lesson' }}</a>@endif
    </li>
    @endforeach
</ol>
