<div class="lesson-reader">@unless($headingShown ?? false)@if($preview ?? false)<h2>{{ $revision->title }}</h2>@else<h1>{{ $revision->title }}</h1>@endif @endunless<p>{{ $revision->description }}</p>
    @if($revision->assessment)<details class="lesson-reading"><summary>Read the lesson before you play</summary>@endif
    <div class="lesson-text">{{ $revision->content }}</div>
    @include('content.media')
    @if($revision->assessment)</details>@endif
</div>
@if($revision->assessment)
    @if($revision->assessment['template'] !== 'choice-quiz')
        @include('learning.activity', ['game' => $revision->assessment, 'module' => null, 'completionUrl' => ($preview ?? false) ? null : ($assessmentCompletionUrl ?? route('modules.game', [$module, $revision->id])), 'trial' => false])
    @else
        @include('learning.quiz', ['quiz' => $revision->assessment, 'module' => null, 'completionUrl' => ($preview ?? false) ? null : ($assessmentCompletionUrl ?? route('modules.quiz', [$module, $revision->id]))])
    @endif
@endif
