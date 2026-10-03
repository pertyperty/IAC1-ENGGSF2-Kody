<?php

namespace App\Services\Challenges\Judge0;

use RuntimeException;

class ProviderUnavailable extends RuntimeException
{
    public function __construct()
    {
        // Do not retain transport exceptions, which can contain credentials or code.
        parent::__construct('Code evaluation is temporarily unavailable.');
    }
}
