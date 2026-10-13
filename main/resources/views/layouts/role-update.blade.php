@if($chrome['roleChange'])
<dialog class="role-update-dialog" data-role-update data-role-url="{{ route('account.role-update') }}" data-role-overview="{{ route('dashboard') }}" data-role-watch="{{ $chrome['roleChange']['watch'] ? '1' : '0' }}" data-role-initial="{{ json_encode($chrome['roleChange']['update']) }}" aria-labelledby="role-update-title" aria-describedby="role-update-message">
    <button class="role-update-close game-reset" type="button" data-role-dismiss aria-label="Go to your overview"><x-action-icon name="close"/></button>
    <div class="role-update-art"><x-kody-art name="celebration" size="150"/><span><x-action-icon name="spark"/> A NEW CHAPTER</span></div>
    <div class="role-update-copy">
        <p class="overline">YOUR APPLICATION WAS APPROVED</p>
        <div class="role-update-badges"><span data-role-previous></span><x-action-icon name="right"/><strong data-role-current></strong></div>
        <h2 id="role-update-title" data-role-title></h2><p id="role-update-message" data-role-message></p>
        <ul data-role-capabilities></ul><p class="field-hint">Your saved learning stays with you. Your new tools are ready.</p>
        <div class="role-update-actions"><button class="button button-play" type="button" data-role-workspace><x-action-icon name="create"/><span data-role-action></span></button><button class="button button-secondary" type="button" data-role-dismiss>Go to your overview</button></div>
        <p class="field-hint" role="status" data-role-status></p>
        <button class="button button-secondary button-small" type="button" data-role-reload hidden>Refresh account <x-action-icon name="refresh"/></button>
    </div>
</dialog>
@if($chrome['roleChange']['update'])
<noscript><aside class="site-announcement"><x-kody-art name="celebration" size="64"/><div><h2>{{ $chrome['roleChange']['update']['title'] }}</h2><p>{{ $chrome['roleChange']['update']['message'] }}</p><form method="post" action="{{ $chrome['roleChange']['update']['acknowledge'] }}">@csrf<button class="button button-play">{{ $chrome['roleChange']['update']['action'] }}</button></form></div></aside></noscript>
@endif
@endif
