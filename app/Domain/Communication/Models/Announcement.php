<?php

declare(strict_types=1);

namespace App\Domain\Communication\Models;

use App\Domain\Schools\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `title_ar` and `body_ar` are bilingual columns the translation layer adds to
 * this table, so they are invisible to static analysis without this.
 *
 * @property string|null $title_ar
 * @property string|null $body_ar
 */
#[Fillable([
    'school_id',
    'title',
    'title_ar',
    'body',
    'body_ar',
    'audience',
    'target_audience',
    'start_date',
    'end_date',
    'is_published',
    'published_by',
    'published_at',
])]
class Announcement extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    protected function casts(): array
    {
        return [
            'target_audience' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
