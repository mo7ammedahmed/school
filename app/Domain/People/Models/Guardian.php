<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property bool|null $is_financial_guardian
 *                                            Populated by Invoice::guardians() to flag the guardian responsible for fees.
 *                                            It is not a database column, so it is only set when loaded that way.
 */
#[Fillable([
    'school_id',
    'user_id',
    'first_name',
    'last_name',
    'relationship',
    'email',
    'phone',
    'national_id_number',
    'passport_number',
    'address',
    // The guardian screens collect and print this; without the column the
    // value was validated and then silently dropped.
    'emergency_contact',
    'occupation',
    'employer',
    'metadata',
])]
class Guardian extends Model
{
    use BelongsToSchool, SoftDeletes;

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<GuardianRelationship, $this> */
    public function relationships(): HasMany
    {
        return $this->hasMany(GuardianRelationship::class);
    }

    /**
     * Children linked through guardian_relationships (belongsToMany via pivot).
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'guardian_relationships',
            'guardian_id',
            'student_id'
        )->withPivot(['is_primary', 'is_financial_guardian']);
    }

    protected function casts(): array
    {
        return [
            'national_id_number' => 'encrypted',
            'metadata' => 'array',
        ];
    }
}
