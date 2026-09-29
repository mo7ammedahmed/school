<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\People\Models\TeacherProfile;

/**
 * A teacher is a row in `teacher_profiles`; this alias derives `teachers`, which
 * has never existed. The public teachers page asked for it and got "no such
 * table" for its trouble. See `ModelTablesTest`.
 */
class Teacher extends TeacherProfile
{
    protected $table = 'teacher_profiles';
}
