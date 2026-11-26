@props(['user', 'showVerified' => true])
@php
    $role = $user->account_role;
    $roleKey = strtolower($role->value);
    $roleIcon = match ($role) {
        \App\Enums\Role::Instructor => 'course',
        \App\Enums\Role::Contributor => 'code',
        \App\Enums\Role::Moderator => 'shield',
        \App\Enums\Role::Administrator => 'crown',
        default => 'spark',
    };
    $verified = $showVerified && $user->hasCreatorBadge();
@endphp
<span class="creator-role creator-role--{{ $roleKey }}" data-role="{{ $roleKey }}"><x-action-icon :name="$roleIcon"/><span>{{ $role->name }}</span></span>@if($verified)<span class="creator-badge creator-badge--{{ $roleKey }}" title="Qualifications reviewed by Kody staff"><x-action-icon name="check"/><span>Verified {{ $role->name }} · Qualifications reviewed</span></span>@endif
