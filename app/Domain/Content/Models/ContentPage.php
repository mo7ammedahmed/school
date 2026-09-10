<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'slug',
    'title',
    'title_ar',
    'content',
    'template',
    'sections',
    'seo_metadata',
    'seo_title',
    'seo_description',
    'canonical_url',
    'robots',
    'is_published',
    'status',
    'published_at',
    'scheduled_at',
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
            'sections' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->where('is_published', true)
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
