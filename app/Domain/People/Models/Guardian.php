<?php

declare(strict_types=1);

namespace App\Domain\People\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    'occupation',
    'employer',
    'metadata',
])]
class Guardian extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(GuardianRelationship::class);
    }

    /**
     * Children linked through guardian_relationships (belongsToMany via pivot).
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
