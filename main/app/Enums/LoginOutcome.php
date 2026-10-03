<?php

namespace App\Enums;

enum LoginOutcome
{
    case Authenticated;
    case Invalid;
    case Unverified;
    case Restricted;
    case Locked;
    case Conflict;

    public function message(): string
    {
        return match ($this) {
            self::Invalid => 'Invalid email or password.',
            self::Unverified => 'Verify your email before signing in.',
            self::Restricted => 'Access to this account is restricted.',
            self::Locked => 'This account is temporarily locked. Try again after the cooldown.',
            self::Conflict => 'Another session is active. Continue to end that session, or cancel.',
            self::Authenticated => 'You are signed in.',
        };
    }
}
