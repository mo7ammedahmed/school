<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $published_at
 */
#[Fillable([
    'school_id',
    'title',
    'title_ar',
    'slug',
    'excerpt',
    'excerpt_ar',
    'content',
    'content_ar',
    'featured_image_path',
    'seo_metadata',
    'is_published',
    'published_at',
])]
#[Appends(['category', 'publish_date'])]
class News extends Model
{
    use SoftDeletes;

    protected function category(): Attribute
    {
        return Attribute::make(get: fn () => 'updates');
    }

    protected function publishDate(): Attribute
    {
        return Attribute::make(get: fn () => $this->published_at?->toDateString());
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
