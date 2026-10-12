<section class="mission-board" aria-labelledby="missions-heading">
    <div class="mission-heading"><div><p class="overline">SMALL WINS. REAL PROGRESS.</p><h2 id="missions-heading">Your mission board</h2><p>Pick one next step. Make a little progress at your pace.</p></div><x-kody-art size="120"/></div>
    <div class="mission-grid">@foreach($missions as $mission)
        <article class="mission-card {{ $mission['done'] ? 'mission-done' : '' }}"><div class="mission-top"><span class="mission-icon" aria-hidden="true"><x-action-icon :name="$mission['icon']"/></span><span class="mission-state">{{ $mission['done'] ? 'Cleared' : 'Ready to play' }}</span></div>
            <h3>{{ $mission['title'] }}</h3><p>{{ $mission['description'] }}</p>
            <progress aria-label="{{ $mission['title'] }} progress" value="{{ $mission['value'] }}" max="{{ $mission['max'] }}"></progress>
            <a class="button {{ $loop->first && !$mission['done'] ? 'button-play' : 'button-secondary' }} button-small" href="{{ $mission['url'] }}">{{ $mission['label'] }} <x-action-icon name="right"/></a>
        </article>
    @endforeach</div>
    <p class="field-hint">These goals reflect saved progress. Reading and local previews do not save a daily win. First article completion may earn XP; previews and replays grant no repeated XP.</p>
</section>
