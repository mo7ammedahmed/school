<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A saved website theme, shared by every school.
 *
 * The one bilingual table with no `school_id`: presets are offered platform-wide,
 * so the sweep treats it as global and the on-save fill falls back to the school
 * the operator is working in for the provider key. It still needs a model, or
 * the preset names and descriptions stay English in the picker.
 */
#[Fillable([
    'name',
    'name_ar',
    'slug',
    'description',
    'description_ar',
    'light_tokens',
    'dark_tokens',
    'typography',
    'radius',
    'shadows',
    'buttons',
    'is_default',
    'enabled',
])]
class WebsiteThemePreset extends Model
{
    protected function casts(): array
    {
        return [
            'light_tokens' => 'array',
            'dark_tokens' => 'array',
            'typography' => 'array',
            'radius' => 'array',
            'shadows' => 'array',
            'buttons' => 'array',
            'is_default' => 'boolean',
            'enabled' => 'boolean',
        ];
    }
}
