@if(auth()->user()?->account_role === \App\Enums\Role::Learner && request()->routeIs('tower.show'))
<button type="button" class="button button-secondary button-small" data-proficiency-open>Choose track</button>
<dialog class="proficiency-choice" data-proficiency-prompt aria-labelledby="proficiency-heading">
    <h2 id="proficiency-heading">Where would you like to start?</h2>
    <p>Choose your current coding experience. You can change tracks anytime.</p>
    @include('layouts.form-errors')
    <form method="post" action="{{ route('tower.proficiency') }}">@csrf
        <fieldset><legend>Your coding experience</legend>
        @foreach(['beginner' => ['Beginner', 'I am new to coding or still learning variables, conditions and loops.'], 'intermediate' => ['Intermediate', 'I can write small programs using loops, functions and collections.'], 'expert' => ['Expert', 'I am comfortable with data structures, algorithm complexity and testing edge cases.']] as $value => [$label, $description])
            <label><input type="radio" name="proficiency" value="{{ $value }}" required @checked(old('proficiency', auth()->user()->coding_proficiency ?? 'beginner') === $value)><span><b>{{ $label }}</b><span>{{ $description }}</span></span></label>
        @endforeach
        </fieldset><button class="button button-play">Start my track</button>
    </form>
    <p class="field-hint">Your cleared levels stay saved.</p>
<button type="button" class="button button-secondary" data-proficiency-close>Keep playing</button>
</dialog>
@endif
