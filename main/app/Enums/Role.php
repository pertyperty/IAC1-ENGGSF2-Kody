<?php

namespace App\Enums;

enum Role: string
{
    case Learner = 'Learner';
    case Contributor = 'Contributor';
    case Instructor = 'Instructor';
    case Moderator = 'Moderator';
    case Administrator = 'Admin';
}
