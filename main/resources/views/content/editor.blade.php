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
        $kind = $assessment === null ? 'none' : ($assessment['template'] === 'choice-quiz' ? 'quiz' : 'game');
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
            <label for="assessment_kind">Make it playable</label><select id="assessment_kind" name="assessment_kind">@foreach(['game' => 'Coding game', 'quiz' => 'Quick quiz', 'preset' => 'Workshop preset', 'none' => 'Lesson only (Article or Video)'] as $value => $label)<option value="{{ $value }}" @selected(old('assessment_kind', $revision?->game_preset_revision_id ? 'preset' : ($revision ? $kind : 'game')) === $value)>{{ $label }}</option>@endforeach</select>
            <div data-preset-fields><h2>Start from a workshop preset</h2><p class="field-hint">Choose saved game or quiz prompts and give the activity a title. Your lesson keeps this version when the preset changes.</p>
                @if($revision?->game_preset_revision_id)<p class="lesson-note">Saved preset revision #{{ $revision->game_preset_revision_id }}. To save a new draft, choose a current available version below.</p>@endif
                <label for="managed_preset">Available preset</label><select id="managed_preset" name="managed_preset"><option value="">Choose a preset</option>@foreach($presets as $preset)<option value="{{ $preset->current_revision_id }}" @selected((string) old('managed_preset', $revision?->game_preset_revision_id) === (string) $preset->current_revision_id)>{{ $preset->name }} · v{{ $preset->currentRevision->number }} · {{ $preset->currentRevision->instance['template'] === 'choice-quiz' ? 'Quick quiz' : 'Coding game' }}</option>@endforeach</select>
                @if($presets->count() === 100)<p class="field-hint">Showing the first 100 available presets.</p>@endif
                <label for="preset_title">Activity title</label><input id="preset_title" name="preset_title" maxlength="100" value="{{ old('preset_title', $assessment['title'] ?? 'My adventure') }}">
            </div>
            <div data-game-fields><h2>Your coding game</h2><p class="field-hint">Choose a coding idea, build its world, then make the prompts your own.</p>
                <label for="game_preset">Game template</label><select id="game_preset" name="game_preset">@foreach((config('learning.instances') + config('arcade')) as $slug => $preset)<option value="{{ $slug }}" @selected(old('game_preset', $assessment['preset'] ?? $assessment['basis'] ?? 'sequences') === $slug)>{{ $preset['title'] }} · {{ $preset['concept'] }}</option>@endforeach</select>
                @foreach(['game_title' => ['Game title',100], 'game_instructions' => ['Instructions',1000], 'game_hint' => ['Hint',1000], 'game_learning_idea' => ['What they learned',1000]] as $name => [$label,$limit])<label for="{{ $name }}">{{ $label }}</label><textarea id="{{ $name }}" name="{{ $name }}" rows="2" maxlength="{{ $limit }}">{{ old($name, $fields[$name]) }}</textarea>@endforeach
                @php
                    $initialGarden = $kind === 'game' && $assessment['template'] === 'command-garden' ? $assessment : config('learning.instances.sequences');
                    $initialLayout = array_intersect_key($initialGarden, array_flip(['start', 'goal', 'path', 'crystals']));
                @endphp
                @include('games.scenario-editor', ['scenarioInstance' => $assessment, 'scenarioName' => 'game_scenario'])
                <section class="garden-designer" data-garden-designer aria-label="Garden level designer">
                    <h3>Build a little world</h3><p>Choose a tool, then tap a tile. K marks the start; the flag is your goal. Keyboard: move between tiles with arrows, then press Enter or Space.</p>
                    <input type="hidden" name="game_layout" value="{{ old('game_layout', json_encode($initialLayout, JSON_THROW_ON_ERROR)) }}">
                    <div class="designer-tools" aria-label="Painting tools">
                        <button type="button" data-designer-tool="path" aria-pressed="true">Path</button>
                        <button type="button" data-designer-tool="start" aria-pressed="false">Kody’s start</button>
                        <button type="button" data-designer-tool="goal" aria-pressed="false">Goal flag</button>
                        <button type="button" data-designer-tool="crystal" aria-pressed="false">Crystal</button>
                    </div>
                    <p class="field-hint">Sequences and conditions allow 12 instructions. Loops repeat a pattern of up to 3 instructions twice. Conditions can collect up to 4 crystals; the starting tile cannot hold one.</p>
                    <div class="designer-board" data-designer-board role="group" aria-label="Editable garden, five columns and four rows"></div>
                    <div class="studio-actions"><button type="button" class="button button-dark" data-designer-preview>Try this level</button><button type="button" class="game-reset" data-designer-reset>Reset trail</button></div>
                    <p class="field-hint" data-designer-status role="status">Your saved world stays intact until you paint a tile or reset it. Published levels change only after review.</p>
                    <div data-designer-preview-host></div>
                    <template>@include('learning.game', ['game' => config('learning.instances.sequences'), 'module' => null, 'designerShell' => true])</template>
                    <noscript><p>Enable JavaScript to paint and try your world. Saving keeps the current layout.</p></noscript>
                </section>
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
@if($module)@can('delete', $module)<p><a class="quiet-link" href="{{ route('content-deletion.show', ['module', $module->id]) }}">Delete this module →</a></p>@endcan
@endif
</section>
@endsection
