<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One block on a website page — a hero, a news list, a call to action.
 *
 * `name` and `content` are the bilingual pair the builder's editor shows, which
 * is why this model has to exist: without it the sweep reported a fully
 * translated site while every section still read English.
 */
#[Fillable([
    'school_id',
    'page_id',
    'type',
    'name',
    'name_ar',
    'order',
    'content',
    'content_ar',
    'settings',
    'styling',
    'enabled',
    'visibility',
])]
class WebsiteSection extends Model
{
    use BelongsToSchool, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(WebsitePage::class, 'page_id');
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'styling' => 'array',
            'enabled' => 'boolean',
        ];
    }
}
