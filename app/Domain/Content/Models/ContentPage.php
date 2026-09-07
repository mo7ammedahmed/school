<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'slug',
    'title',
    'content',
    'seo_metadata',
    'is_published',
    'published_at',
])]
class ContentPage extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
    protected function casts(): array
    {
        return [
            'seo_metadata' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
