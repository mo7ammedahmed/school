<?php

declare(strict_types=1);

namespace App\Domain\Communication\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Collection<int, Message> $messages
 */
#[Fillable([
    'school_id',
    'type',
    'subject',
])]
class Conversation extends Model
{
    use BelongsToSchool, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
