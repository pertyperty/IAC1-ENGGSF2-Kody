<?php

namespace App\Services\Account;

use App\Models\AccountRecovery;
use App\Models\EmailVerification;
use App\Models\User;
use App\Models\VerificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class AccountSecurity
{
    // Caller holds the account lock and commits this with its sensitive state change.
    public function revoke(User $user): void
    {
        if (config('session.driver') === 'database'
            && (config('session.connection') ?? config('database.default')) !== config('database.default')) {
            throw new LogicException('Account changes require database sessions on the application database.');
        }
        $user->forceFill(['remember_token' => Str::random(60), 'active_session_hash' => null, 'active_session_expires_at' => null]);
        AccountRecovery::where('user_id', $user->id)->update(['token_hash' => null, 'expires_at' => null]);
        EmailVerification::where('user_id', $user->id)->update(['token_hash' => null, 'expires_at' => null]);
        VerificationDelivery::where('user_id', $user->id)->whereNull('sent_at')->whereNull('cancelled_at')
            ->update(['token' => null, 'cancelled_at' => now()]);
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $user->id)->delete();
        }
    }
}
