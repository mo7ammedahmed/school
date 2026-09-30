<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A page built in the website builder.
 *
 * The table had `title_ar` and `description_ar` columns from the bilingual
 * migration but no model, so those two columns were unreachable: the sweep could
 * not see them and the on-save fill never ran for a save. A model is what makes
 * a bilingual table exist as far as the translator is concerned.
 */
#[Fillable([
    'school_id',
    'slug',
    'title',
    'title_ar',
    'description',
    'description_ar',
    'template',
    'status',
    'published_at',
    'seo_metadata',
    'settings',
])]
class WebsitePage extends Model
{
    use BelongsToSchool, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(WebsiteSection::class, 'page_id')->orderBy('order');
    }

    protected function casts(): array
    {
        return [
            'seo_metadata' => 'array',
            'settings' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
