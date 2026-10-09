<section class="mission-board" aria-labelledby="missions-heading">
    <div class="mission-heading"><div><p class="overline">SMALL WINS. REAL PROGRESS.</p><h2 id="missions-heading">Your mission board</h2><p>Pick one next step. Make a little progress at your pace.</p></div><img src="{{ asset('images/kody-explorer.svg') }}" width="120" height="100" alt="" aria-hidden="true"></div>
    <div class="mission-grid">@foreach($missions as $mission)
        <article class="mission-card {{ $mission['done'] ? 'mission-done' : '' }}"><div class="mission-top"><span class="mission-icon" aria-hidden="true">{{ $mission['icon'] }}</span><span class="mission-state">{{ $mission['done'] ? 'Cleared ✓' : 'Ready to play' }}</span></div>
            <h3>{{ $mission['title'] }}</h3><p>{{ $mission['description'] }}</p>
            <progress aria-label="{{ $mission['title'] }} progress" value="{{ $mission['value'] }}" max="{{ $mission['max'] }}"></progress>
            <a class="button {{ $loop->first && !$mission['done'] ? 'button-play' : 'button-secondary' }} button-small" href="{{ $mission['url'] }}">{{ $mission['label'] }} →</a>
        </article>
    @endforeach</div>
    <p class="field-hint">These goals reflect saved progress. Reading and local previews do not save a daily win or award XP; replays do not repeat first-clear XP.</p>
</section>
