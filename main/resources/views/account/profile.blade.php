@extends('layouts.account')
@section('title', 'Your account — Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
    <header class="catalog-header">
        <div><h1>Your profile</h1><p>Manage your account and public details.</p></div>
        <a class="button button-play" href="{{ route('account.edit') }}">Edit profile</a>
    </header>
    <div class="profile-overview"><section class="profile-identity">
    <a class="profile-picture-link" href="{{ route('account.edit') }}#profile-picture" aria-label="Edit your profile picture">@include('layouts.avatar', ['avatarUser' => auth()->user(), 'avatarClass' => 'profile-avatar'])<span class="profile-edit-icon" aria-hidden="true"><x-action-icon name="pencil"/></span></a><span class="profile-edit-cue">Edit picture</span>
    <h2>{{ $profile['username'] ?? 'Kody player' }}</h2><p class="profile-role-line"><x-role-badge :user="auth()->user()" :show-verified="true"/><span class="status-pill" data-status="{{ $profile['status'] }}">{{ $profile['status'] }}</span></p>
    @if(!$profile['first_name'] && !$profile['last_name'])<p class="field-hint">Your name is optional. You can use Kody with just your username.</p>@endif
    <div class="profile-tools"><a class="button button-secondary" href="{{ route('account.google') }}">Manage Google sign-in</a><a class="button button-secondary" href="{{ route('wallet.index') }}"><x-kody-art name="kodebit" size="28"/> Wallet and earnings</a></div>
    </section><section><h2>Account details</h2><p class="field-hint">Your email is masked for privacy.</p>
    <dl class="profile-details">
        <dt>Username</dt><dd>{{ $profile['username'] ?? 'Not yet provided' }}</dd>
        <dt>First name</dt><dd>{{ $profile['first_name'] ?? 'Not yet provided' }}</dd>
        <dt>Last name</dt><dd>{{ $profile['last_name'] ?? 'Not yet provided' }}</dd>
        <dt>Email</dt><dd>{{ $profile['email'] }}</dd>
        <dt>Role</dt><dd>{{ $profile['role'] }}</dd>
        <dt>Status</dt><dd>{{ $profile['status'] }}</dd>
        <dt>Joined</dt><dd>{{ $profile['joined_at'] ?? 'Not available' }}</dd>
    </dl>
    @if($application)<div class="notice"><b>Instructor application: {{ $application->verification_status }}</b>@if($application->verification_notes)<p>{{ $application->verification_notes }}</p>@endif</div>@endif
    </section></div><div class="profile-support">
    @if(in_array(auth()->user()->account_role, [\App\Enums\Role::Learner, \App\Enums\Role::Contributor], true))
    <div class="profile-actions" aria-label="Role applications">
        <a href="{{ route('instructor-application.create') }}">Apply as an instructor</a>
        @can('viewOwn', \App\Models\ContributorApplication::class)<a href="{{ route('contributor-application.create') }}">Contributor application and history</a>@endcan
    </div>
    @endif
    <div class="profile-actions">
        @can('create', \App\Models\LearningModule::class)<a href="{{ route('studio.index') }}">Open your creator studio</a>@endcan
        @can('create', \App\Models\CodingChallenge::class)<a href="{{ route('challenges.index') }}">Open your challenge studio</a>@endcan
        <a href="{{ route('notifications.index') }}">Your updates</a>
    </div>
    @if(app(\App\Services\Account\CreatorProfiles::class)->visible(auth()->user()))<div class="profile-actions"><a href="{{ route('creators.show', auth()->user()) }}">View public creator profile</a><a href="{{ route('creator-profile.edit') }}">Edit public details</a></div>@endif
    @include('layouts.staff-tools')
    @can('archive', auth()->user())<p class="secondary-link"><a href="{{ route('account.archive') }}">Archive your account</a></p>@endcan
    @can('delete', auth()->user())<p class="secondary-link"><a href="{{ route('account.delete') }}">Permanently delete your account</a></p>@endcan
    </div>
@endsection
