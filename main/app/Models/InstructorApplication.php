<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id'])]
#[Hidden(['credential_disk', 'credential_path'])]
class InstructorApplication extends Model {}
