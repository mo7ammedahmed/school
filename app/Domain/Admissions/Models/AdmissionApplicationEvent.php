<?php

declare(strict_types=1);

namespace App\Domain\Admissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User|null $user
 */
#[Fillable([
    'admission_application_id',
    'user_id',
    'event_type',
    'notes',
    'notes_ar',
])]
class AdmissionApplicationEvent extends Model
{
    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }
}
