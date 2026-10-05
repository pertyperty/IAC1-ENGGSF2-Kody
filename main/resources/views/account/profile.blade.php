@extends('layouts.account')
@section('title', 'Your account — Kody')
@section('content')
    <p class="eyebrow">YOUR KODY ACCOUNT</p>
    <h1>Your profile</h1>
    <p class="intro">Your account information. Your email is masked for privacy.</p>
    <p class="secondary-link"><a href="{{ route('account.edit') }}">Edit your player profile</a></p>
    <p class="secondary-link"><a href="{{ route('account.google') }}">Manage Google sign-in</a></p>
    @if(in_array(auth()->user()->account_role, [\App\Enums\Role::Learner, \App\Enums\Role::Contributor], true))<p class="secondary-link"><a href="{{ route('instructor-application.create') }}">Become a learning creator</a></p>@endif
    <p class="secondary-link"><a href="{{ route('wallet.index') }}">Your KodeBits and earnings</a></p>
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
    @can('viewAny', \App\Models\InstructorApplication::class)<p class="secondary-link"><a href="{{ route('instructor-reviews.index') }}">Review instructor applications</a></p>@endcan
    @can('create', \App\Models\LearningModule::class)<p class="secondary-link"><a href="{{ route('studio.index') }}">Open your creator studio</a></p>@endcan
    @can('viewAny', \App\Models\LearningModule::class)<p class="secondary-link"><a href="{{ route('module-reviews.index') }}">Review adventures</a></p>@endcan
    <p class="secondary-link"><a href="{{ route('notifications.index') }}">Your updates</a></p>
    @can('viewOwn', \App\Models\ContributorApplication::class)<p class="secondary-link"><a href="{{ route('contributor-application.create') }}">Contributor application and history</a></p>@endcan
    @can('viewAny', \App\Models\ContributorApplication::class)<p class="secondary-link"><a href="{{ route('contributor-reviews.index') }}">Review Contributor applications</a></p>@endcan
    @can('viewAny', \App\Models\User::class)<p class="secondary-link"><a href="{{ route('account-governance.index') }}">Community account governance</a></p>@endcan
    @if(in_array(auth()->user()->account_role, [\App\Enums\Role::Moderator, \App\Enums\Role::Administrator], true))<p class="secondary-link"><a href="{{ route('creator-erasure.index') }}">Review creator privacy inventories</a></p>@endif
    @if(auth()->user()->account_role === \App\Enums\Role::Administrator)<p class="secondary-link"><a href="{{ route('finance.index') }}">Finance and reconciliation</a> · <a href="{{ route('system-reports') }}">System reports</a></p>@endif
    @can('archive', auth()->user())<p class="secondary-link"><a href="{{ route('account.archive') }}">Take a break: archive your account</a></p>@endcan
    @can('delete', auth()->user())<p class="secondary-link"><a href="{{ route('account.delete') }}">Permanently delete your account</a></p>@endcan
    <p class="secondary-link"><a href="{{ route('dashboard') }}">Back to your account home</a></p>
@endsection
