<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'menu_id',
    'label_ar',
    'label_en',
    'type',
    'page_id',
    'url',
    'route',
    'target',
    'icon',
    'order',
    'enabled',
    'dropdown',
    'settings',
])]
#[Appends([
    'label',
])]
class WebsiteNavigationItem extends Model
{
    use SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(WebsiteNavigationMenu::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(ContentPage::class);
    }

    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"label_$locale"} ?? $this->label_en;
    }
}
