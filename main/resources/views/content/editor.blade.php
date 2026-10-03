@extends('layouts.learning')
@section('title', 'Build an adventure — Kody')
@section('content')


<section class="review-page page-width">
@if($module?->isWithdrawn())<p class="lesson-note">Staff withdrawal is active. Learner access is blocked. Draft corrections, publication review and creator archival do not lift this block; only staff restoration can do that.</p>@endif

    <a class="quiet-link" href="{{ route('studio.index') }}">← Your studio</a><h1>{{ $module ? 'Shape your adventure.' : 'Start with a spark.' }}</h1>
    <p>Teach a small idea with a lesson and a playful activity. Save a draft, try it out, then submit it for review.</p>
    @if(session('status'))<p role="status" class="lesson-note">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="studio-errors"><b>Please check your adventure.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($revision)<p class="step-pill">Revision {{ $revision->number }} · {{ $revision->review_status }} · {{ $module->status }}</p>@if($revision->review_notes)<p class="lesson-note">Reviewer feedback: {{ $revision->review_notes }}</p>@endif @endif
    @php
        $assessment = $revision?->assessment;
        $kind = $assessment === null ? 'none' : ($assessment['template'] === 'command-garden' ? 'game' : 'quiz');
        $fields = ['title' => $revision?->title, 'description' => $revision?->description, 'content' => $revision?->content,
            'video_url' => $revision?->video_url, 'game_title' => $kind === 'game' ? $assessment['title'] : 'My Logic Garden',
            'game_instructions' => $kind === 'game' ? $assessment['instructions'] : config('learning.instances.sequences.instructions'),
            'game_hint' => $kind === 'game' ? $assessment['hint'] : config('learning.instances.sequences.hint'),
            'game_learning_idea' => $kind === 'game' ? $assessment['learningIdea'] : config('learning.instances.sequences.learningIdea'),
            'quiz_title' => $kind === 'quiz' ? $assessment['title'] : 'A quick idea check',
            'quiz_question' => $kind === 'quiz' ? $assessment['question'] : '', 'quiz_a' => $kind === 'quiz' ? $assessment['options'][0]['label'] : '',
            'quiz_b' => $kind === 'quiz' ? $assessment['options'][1]['label'] : '', 'quiz_explanation' => $kind === 'quiz' ? $assessment['explanation'] : ''];
    @endphp
    <form class="studio-form" method="post" action="{{ $module ? route('studio.update', $module) : route('studio.store') }}" data-module-editor data-game-presets="{{ json_encode(config('learning.instances'), JSON_THROW_ON_ERROR) }}">
        @csrf @if($module)@method('put')@endif
        <input type="hidden" name="record_version" value="{{ $module?->record_version ?? 1 }}">
        <fieldset @disabled($revision?->review_status === 'Pending' || $module?->status === 'Archived')>
            <legend>Your lesson</legend>
            <label for="title">Adventure title</label><input id="title" name="title" value="{{ old('title', $fields['title']) }}" maxlength="150" required>
            <label for="description">A little invitation</label><textarea id="description" name="description" rows="3" maxlength="5000" required>{{ old('description', $fields['description']) }}</textarea>
            <label for="type">Lesson format</label><select id="type" name="type">@foreach(['Article','Interactive','Video'] as $type)<option @selected(old('type', $revision?->type ?? 'Interactive') === $type)>{{ $type }}</option>@endforeach</select>
            <label for="content">Teach the idea</label><p class="field-hint">Write your explanation and code examples as plain text. Your learners will see exactly what you write.</p><textarea id="content" name="content" rows="10" maxlength="50000" required>{{ old('content', $fields['content']) }}</textarea>
            <div data-video-fields><label for="video_url">Video link</label><p class="field-hint">For video lessons, add an HTTPS link to your video. It opens in a new tab.</p><input id="video_url" type="url" name="video_url" value="{{ old('video_url', $fields['video_url']) }}" maxlength="2000"></div>
            <label for="assessment_kind">Make it playable</label><select id="assessment_kind" name="assessment_kind">@foreach(['game' => 'Garden game', 'quiz' => 'Quick quiz', 'none' => 'Lesson only (Article or Video)'] as $value => $label)<option value="{{ $value }}" @selected(old('assessment_kind', $revision ? $kind : 'game') === $value)>{{ $label }}</option>@endforeach</select>
            <div data-game-fields><h2>Your garden game</h2><p class="field-hint">Choose a trail, then make its prompts your own. The trail controls the goal and movement rules.</p>
                <label for="game_preset">Trail</label><select id="game_preset" name="game_preset">@foreach(config('learning.modules') as $slug => $preset)<option value="{{ $slug }}" @selected(old('game_preset', $assessment['preset'] ?? 'sequences') === $slug)>{{ $preset['concept'] }}</option>@endforeach</select>
                @foreach(['game_title' => ['Game title',100], 'game_instructions' => ['Instructions',1000], 'game_hint' => ['Hint',1000], 'game_learning_idea' => ['What they learned',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $fields[$name]) }}</textarea>@endforeach
            </div>
            <div data-quiz-fields><h2>Your quick quiz</h2><p class="field-hint">Two clear choices, one useful idea. This is a practice quiz with feedback.</p>
                @foreach(['quiz_title' => ['Quiz title',100], 'quiz_question' => ['Question',500], 'quiz_a' => ['Choice A',300], 'quiz_b' => ['Choice B',300], 'quiz_explanation' => ['Feedback and explanation',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $fields[$name]) }}</textarea>@endforeach
                <label for="quiz_answer">Correct choice</label><select id="quiz_answer" name="quiz_answer"><option value="a" @selected(old('quiz_answer', $assessment['answer'] ?? 'a') === 'a')>Choice A</option><option value="b" @selected(old('quiz_answer', $assessment['answer'] ?? 'a') === 'b')>Choice B</option></select>
            </div>
            <div class="studio-actions"><button class="button button-play" type="submit">Save draft</button><a class="quiet-link" href="{{ route('studio.index') }}">Cancel</a></div>
        </fieldset>
    </form>
    @if($revision?->review_status === 'Draft' && $module?->status !== 'Archived')<form method="post" action="{{ route('studio.submit', $module) }}" class="studio-actions">@csrf<input type="hidden" name="record_version" value="{{ $module->record_version }}"><button class="button button-dark" type="submit">Submit saved draft for review →</button></form>@endif
    @if($module?->status === 'Published')<p><a class="quiet-link" href="{{ route('studio.archive-confirmation', $module) }}">Archive this adventure</a></p>@endif
    @if($revision)<section class="studio-preview"><h2>Try your saved adventure</h2><p class="field-hint">This preview uses your last saved draft. Preview wins stay here.</p>@include('content.lesson', ['module' => null, 'preview' => true])</section>@endif
</section>
@endsection
