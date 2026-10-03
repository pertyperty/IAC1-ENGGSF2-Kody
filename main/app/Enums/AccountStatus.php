<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Unverified = 'Unverified';
    case Active = 'Active';
    case Suspended = 'Suspended';
    case Archived = 'Archived';
    case Deleted = 'Deleted';
}
