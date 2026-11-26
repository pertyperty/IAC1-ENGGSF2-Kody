@extends('layouts.account')
@section('title', 'Get started with the Tower | Kody')
@section('shell-class', 'account-shell-wide')
@section('content')
    @if ($selected === null)
        <p class="overline">TOWER SETUP: STEP 1 OF 2</p>
        <h1>Choose your coding level</h1>
        <p class="intro">Start where you feel comfortable. You can switch tracks later.</p>
        <form action="{{ route('tower.proficiency') }}" method="post" class="account-form tower-onboarding-form">
            @csrf
            <fieldset>
                <legend>Your starting level</legend>
                @foreach ($tracks as $key => $track)
                    <label class="tower-onboarding-choice">
                        <input type="radio" name="proficiency" value="{{ $key }}" required>
                        <span><strong>{{ ucfirst($key) }}</strong><small>{{ $track['description'] }}</small></span>
                    </label>
                @endforeach
            </fieldset>
            @include('layouts.form-errors')
            <button class="primary-button" type="submit">Continue to tutorial</button>
        </form>
    @else
        <p class="overline">TOWER SETUP: STEP 2 OF 2</p>
        <h1>How to play the Tower</h1>
        <p class="intro">Your starting level is <strong>{{ ucfirst($selected) }}</strong>.</p>
        <ol class="tower-onboarding-steps">
            <li><strong>Pick an available floor.</strong> Start with the first open floor on your map.</li>
            <li><strong>Build a program and press Run.</strong> For example, <code>kody.move_right()</code> moves Kody one space to the right when the program runs.</li>
            <li><strong>Use the feedback to try again.</strong> Clearing a floor saves your progress and opens the next one in your track.</li>
        </ol>
        <form action="{{ route('tower.onboarding.finish') }}" method="post" class="account-form">
            @csrf
            <button class="primary-button" type="submit">Open Tower map</button>
        </form>
    @endif
@endsection
