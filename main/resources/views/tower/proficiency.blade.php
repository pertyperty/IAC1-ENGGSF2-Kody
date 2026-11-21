@if(auth()->user()?->account_role === \App\Enums\Role::Learner && auth()->user()->coding_proficiency === null)
<dialog class="proficiency-choice" data-proficiency-prompt aria-labelledby="proficiency-heading">
    <h2 id="proficiency-heading">Where would you like to start?</h2>
    <p>Choose your current coding experience. You can change tracks anytime.</p>
    @include('layouts.form-errors')
    <form method="post" action="{{ route('tower.proficiency') }}">@csrf
        <fieldset><legend>Your coding experience</legend>
        @foreach(['beginner' => ['Beginner', 'I am new to coding or still learning variables, conditions and loops.'], 'intermediate' => ['Intermediate', 'I can write small programs using loops, functions and collections.'], 'expert' => ['Expert', 'I am comfortable with data structures, algorithm complexity and testing edge cases.']] as $value => [$label, $description])
            <label><input type="radio" name="proficiency" value="{{ $value }}" required @checked(old('proficiency') === $value)><span><b>{{ $label }}</b><span>{{ $description }}</span></span></label>
        @endforeach
        </fieldset><button class="button button-play">Start my track</button>
    </form>
    <p class="field-hint">Each track starts independently. This choice grants no completed levels or qualification.</p>
<button type="button" class="button button-secondary" data-proficiency-close>Explore first</button>
</dialog>
@endif
