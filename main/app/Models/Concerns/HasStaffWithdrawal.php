<?php

namespace App\Models\Concerns;

trait HasStaffWithdrawal
{
    public function isWithdrawn(): bool
    {
        return $this->staff_withdrawn_at !== null;
    }
}
