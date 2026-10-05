<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read School|null $school
 */
#[Fillable([
    'school_id',
    'slug',
    'title',
    'title_ar',
    'content',
    'content_ar',
    'show_in_navigation',
    'navigation_order',
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
    use BelongsToSchool, SoftDeletes;

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
            'show_in_navigation' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function published($query)
    {
        return $query->where(function ($query): void {
            $query->where(function ($published): void {
                $published->where('status', 'published')->where('is_published', true)
                    ->where(function ($date): void {
                        $date->whereNull('published_at')->orWhere('published_at', '<=', now());
                    });
            })->orWhere(function ($scheduled): void {
                $scheduled->where('status', 'scheduled')->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now());
            });
        });
    }
}
