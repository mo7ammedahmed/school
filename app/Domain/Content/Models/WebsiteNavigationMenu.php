<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Append;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name_ar',
    'name_en',
    'slug',
    'enabled',
])]
#[Append([
    'name',
])]
class WebsiteNavigationMenu extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WebsiteNavigationItem::class);
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_$locale"} ?? $this->name_en;
    }
}
