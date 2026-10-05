@extends('layouts.learning')
@section('title', $revision->title.' — Kody')
@section('content')
<section class="review-page page-width">
    <p class="eyebrow">YOUR NEXT ADVENTURE</p><h1>{{ $revision->title }}</h1>
    <p class="intro">{{ $price === 0 ? 'Free access' : $price.' KodeBits' }} · Unlock once to continue learning.</p>
    @if($errors->any())<div class="notice" role="alert">{{ $errors->first() }}</div>@endif
    @if(!$requirements['eligible'])<p class="lesson-note">Reach {{ $requirements['minimum_xp'] }} XP and complete the required assessments first. Adventures still to clear: {{ implode(', ', $requirements['missing_titles']) ?: 'none' }}.</p>@endif
    @if($kind === 'challenge' && !($executionReady ?? false))<p class="lesson-note">Code evaluation is being prepared. Unlocking will open when it is ready; no KodeBits have been used.</p>@elseif($participant && $requirements['eligible'])
    <form method="post" action="{{ route('content-access.store', [$kind, $target->id]) }}" class="account-form">@csrf
        <input type="hidden" name="revision_id" value="{{ $revision->id }}">
        <label class="checkbox-label"><input type="checkbox" name="confirmed" value="1" required> I confirm {{ $price === 0 ? 'free access' : 'spending '.$price.' KodeBits' }} for this {{ $kind }}.</label>
        <button class="button button-play">Unlock adventure →</button>
    </form>@endif
    <p><a class="quiet-link" href="{{ route('wallet.index') }}">View your wallet</a></p>
</section>
@endsection
