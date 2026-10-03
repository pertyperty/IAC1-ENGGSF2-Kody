@extends('layouts.learning')
@section('title', 'Shape a course — Kody')
@section('content')
<section class="review-page page-width"><a class="quiet-link" href="{{ route('courses.index') }}">← Your courses</a><h1>Give your journey a shape.</h1><p>Start with one clear goal. Build toward it with small adventures.</p>
    @if(session('status'))<p class="lesson-note" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="studio-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if($revision)<p class="step-pill">Revision {{ $revision->number }} · {{ $revision->review_status }}</p>@if($revision->review_notes)<p class="lesson-note">{{ $revision->review_notes }}</p>@endif @endif
    <form method="get" class="catalog-search"><label for="course-adventure-search">Find adventures to add (save changes before searching)</label><div><input id="course-adventure-search" type="search" name="q" value="{{ request('q') }}" maxlength="80" placeholder="Search your published adventures"><button class="button button-dark button-small">Find</button></div></form>
    <form class="studio-form" method="post" action="{{ $course ? route('courses.update', $course) : route('courses.store') }}">@csrf @if($course)@method('put')@endif<input type="hidden" name="record_version" value="{{ $course?->record_version ?? 1 }}">
        <fieldset @disabled($revision?->review_status === 'Pending')><legend>Your course</legend>
            <label for="title">Course title</label><input id="title" name="title" maxlength="150" value="{{ old('title', $revision?->title) }}" required>
            <label for="description">The big idea</label><textarea id="description" name="description" rows="5" maxlength="5000" required>{{ old('description', $revision?->description) }}</textarea>
            <label for="category">Category</label><input id="category" name="category" maxlength="50" value="{{ old('category', $revision?->category) }}" placeholder="Programming basics" required>
            <label for="difficulty">Where does the journey start?</label><select id="difficulty" name="difficulty">@foreach(['Beginner','Intermediate','Advanced'] as $difficulty)<option @selected(old('difficulty', $revision?->difficulty ?? 'Beginner') === $difficulty)>{{ $difficulty }}</option>@endforeach</select>
            <label for="estimated_duration">Estimated learning time (hours)</label><input id="estimated_duration" name="estimated_duration" type="number" min="1" max="10000" value="{{ old('estimated_duration', $revision?->estimated_duration ?? 1) }}" required>
            <input type="hidden" name="module_ids[]" value="">
            <section data-course-composer><h2>Put your adventures in order.</h2><p class="field-hint">Choose from your latest 200 published adventures. Each slot saves the approved lesson version shown here. Remove a slot to leave it out of the journey.</p>
                @php
                    $selected = old('module_ids', $revision?->modules->pluck('module_id')->all() ?? []);
                    $selected = is_array($selected) ? array_slice($selected, 0, 100) : [];
                    if(count($selected) < 100) $selected[] = '';
                @endphp
                <div data-course-slots>@foreach($selected as $choice)<div class="course-slot"><label>Adventure <span data-slot-number>{{ $loop->iteration }}</span><select name="module_ids[]"><option value="">Choose an adventure</option>@foreach($modules as $adventure)<option value="{{ $adventure->id }}" @selected(is_scalar($choice) && (string)$choice === (string)$adventure->id)>{{ $adventure->publishedRevision->title }} · v{{ $adventure->publishedRevision->number }} · {{ $adventure->status }}</option>@endforeach</select></label><button type="button" class="game-reset" data-remove-slot>Remove</button></div>@endforeach</div>
                <template data-course-slot-template><div class="course-slot"><label>Adventure <span data-slot-number></span><select name="module_ids[]"><option value="">Choose an adventure</option>@foreach($modules as $adventure)<option value="{{ $adventure->id }}">{{ $adventure->publishedRevision->title }} · v{{ $adventure->publishedRevision->number }} · {{ $adventure->status }}</option>@endforeach</select></label><button type="button" class="game-reset" data-remove-slot>Remove</button></div></template>
                <button class="button button-dark button-small" type="button" data-add-slot>Add an adventure +</button>
            </section>
            <div class="studio-actions"><button class="button button-play">Save course draft</button><a class="quiet-link" href="{{ route('courses.index') }}">Cancel</a></div>
        </fieldset>
    </form>
    @if($revision?->review_status === 'Draft')<form method="post" action="{{ route('courses.submit', $course) }}" class="studio-actions">@csrf<input type="hidden" name="record_version" value="{{ $course->record_version }}"><button class="button button-dark">Submit saved course for review →</button></form>@endif
    @if($revision)<h2>Try your saved journey</h2>@include('content.course-outline')@endif
</section>
@endsection
