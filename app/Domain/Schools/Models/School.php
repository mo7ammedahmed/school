<?php

declare(strict_types=1);

namespace App\Domain\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use App\Domain\People\Models\Student;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Identity\Models\UserMembership;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\SchoolFactory;

#[Fillable([
    'organization_id',
    'name',
    'slug',
    'email',
    'phone',
    'address',
    'city',
    'country',
    'timezone',
    'locale',
    'currency',
    'logo_path',
    'favicon_path',
    'primary_color',
    'secondary_color',
    'metadata',
])]
#[UseFactory(SchoolFactory::class)]
class School extends Model
{
    use SoftDeletes, HasFactory;


    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
