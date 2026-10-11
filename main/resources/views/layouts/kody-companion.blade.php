<div class="kody-companion" data-companion hidden>
    <div class="companion-bubble" data-companion-bubble hidden><b>Your little coding companion</b><p>Drag me somewhere comfortable, or use arrow keys while my button is focused.</p><button type="button" class="button button-secondary button-small" data-tour-open>Show me around</button><button type="button" class="button button-secondary button-small" data-companion-hide>Hide companion</button></div>
    <button class="companion-handle" type="button" data-companion-handle aria-label="Kody companion: drag to move, click for help" aria-expanded="false"><x-kody-art size="80"/></button>
</div>
<dialog class="kody-tour" data-kody-tour data-tour-page="{{ request()->route()?->getName() ?? 'error' }}" data-guest="{{ auth()->guest() ? 'true' : 'false' }}" aria-labelledby="tour-title" aria-describedby="tour-message">
    <svg class="tour-shade" aria-hidden="true"><defs><mask id="tour-window"><rect width="100%" height="100%" fill="white"/><circle data-tour-hole fill="black" r="0"/></mask></defs><rect width="100%" height="100%" fill="rgba(6,15,32,.78)" mask="url(#tour-window)"/></svg>
    <button type="button" class="tour-spot" data-tour-spot aria-label="Continue this tour tip" hidden></button>
    <section class="tour-card" data-tour-card>
        <x-kody-art class="tour-mascot" size="120"/>
        <div class="tour-copy"><p class="overline" data-tour-count>WELCOME TO KODY</p><h2 id="tour-title">A little play. A lot of possibility.</h2><p id="tour-message">I’m Kody, your coding companion. Your adventure is ready. Let’s find our way around.</p></div>
        <div class="tour-actions"><button class="button button-play" type="button" data-tour-next>Click to begin</button><button class="button button-secondary" type="button" data-tour-back hidden>Previous</button><button class="button button-secondary" type="button" data-tour-skip>Skip tutorial</button>@guest<a class="button button-secondary" href="{{ route('login') }}">Log in</a>@endguest</div>
    </section>
</dialog>
<div class="page-loader" data-page-loader hidden role="status" aria-live="polite"><x-kody-art size="96"/><span>Finding your next adventure…</span><span class="loading-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
