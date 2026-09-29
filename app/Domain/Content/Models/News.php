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
    // The admin form, the admin screens and both public news screens carry this;
    // without the column the value was validated and then dropped on save.
    'category',
    'excerpt',
    'excerpt_ar',
    'content',
    'content_ar',
    'featured_image_path',
    'seo_metadata',
    'is_published',
    'published_at',
])]
// `publish_date` is a display name for `published_at`; `category` is a real
// column now, so it comes back on its own without being appended.
#[Appends(['publish_date'])]
class News extends Model
{
    use SoftDeletes;

    // There used to be a `category()` accessor here that returned the literal
    // string `updates` for every row. It is why the missing column went unnoticed
    // for so long: the admin list, the admin detail screen and both public news
    // screens all printed "Updates" for every article, which looked like data.
    // The column exists now, so the real value is what comes back.

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
