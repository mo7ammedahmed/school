<?php

declare(strict_types=1);

namespace App\Domain\Admissions\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admission_application_id',
    'user_id',
    'event_type',
    'notes',
])]
class AdmissionApplicationEvent extends Model
{
    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }
}
