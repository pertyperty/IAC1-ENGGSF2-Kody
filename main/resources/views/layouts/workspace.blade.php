@auth
<aside class="app-rail" data-navigation-rail>
    <button class="rail-toggle" type="button" data-rail-toggle aria-expanded="false" aria-label="Keep navigation expanded"><svg width="22" height="22" aria-hidden="true"><use href="{{ asset('images/navigation.svg') }}#panel"/></svg><span class="rail-toggle-label">Pin navigation</span></button>
    <div class="rail-identity"><img src="{{ asset(config('branding.mark')) }}" width="32" height="32" alt=""><div><b>{{ auth()->user()->username }}</b><span>{{ auth()->user()->account_role->name }}</span></div></div>
    @include('layouts.workspace-nav')
</aside>
<details class="mobile-workspace"><summary>Explore your workspace <span aria-hidden="true">⌄</span></summary>@include('layouts.workspace-nav', ['mobile' => true])</details>
@endauth
