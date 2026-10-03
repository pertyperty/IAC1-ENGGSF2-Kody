<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'username', 'first_name', 'last_name'])]
#[Hidden(['password', 'remember_token', 'active_session_hash', 'active_session_expires_at', 'failed_login_attempts', 'login_locked_until'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_role' => Role::class,
            'account_status' => AccountStatus::class,
            'failed_login_attempts' => 'integer',
            'login_locked_until' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'profile_version' => 'integer',
            'anonymized_at' => 'immutable_datetime',
            'active_session_expires_at' => 'immutable_datetime',
        ];
    }
}
