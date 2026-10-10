@auth
<aside class="app-rail">
    <div class="rail-identity"><span class="rail-emblem" aria-hidden="true">k</span><div><b>@can('viewAny', \App\Models\User::class)Your community workspace.@else Make a little progress.@endcan</b><span>{{ auth()->user()->account_role->name }}</span></div></div>
    @include('layouts.workspace-nav')
</aside>
<details class="mobile-workspace"><summary>Explore your workspace <span aria-hidden="true">⌄</span></summary>@include('layouts.workspace-nav', ['mobile' => true])</details>
@endauth
