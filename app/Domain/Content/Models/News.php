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
    'title',
    'slug',
    'excerpt',
    'content',
    'featured_image_path',
    'seo_metadata',
    'is_published',
    'published_at',
])]
class News extends Model
{
    use SoftDeletes;

    /** Legacy admin-page aliases (the v2 schema tracks publish state + time explicitly). */
    protected $appends = ['category', 'publish_date'];

    public function getCategoryAttribute(): string
    {
        return 'updates';
    }

    public function getPublishDateAttribute()
    {
        return $this->published_at?->toDateString();
    }

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
