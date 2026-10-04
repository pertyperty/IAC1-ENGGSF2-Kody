<div class="lesson-note">@if($preview ?? false)<h2>{{ $revision->title }}</h2>@else<h1>{{ $revision->title }}</h1>@endif<p>{{ $revision->description }}</p><div class="lesson-text">{{ $revision->content }}</div>
    @if($revision->type === 'Video')<p><a class="quiet-link" href="{{ $revision->video_url }}" target="_blank" rel="noopener noreferrer">Watch the lesson video ↗</a></p>@endif
</div>
@if($revision->assessment)
    @if($revision->assessment['template'] !== 'choice-quiz')
        @include('learning.activity', ['game' => $revision->assessment, 'module' => null, 'completionUrl' => ($preview ?? false) ? null : ($assessmentCompletionUrl ?? route('modules.game', [$module, $revision->id])), 'trial' => false])
    @else
        @include('learning.quiz', ['quiz' => $revision->assessment, 'module' => null, 'completionUrl' => ($preview ?? false) ? null : ($assessmentCompletionUrl ?? route('modules.quiz', [$module, $revision->id]))])
    @endif
@endif
