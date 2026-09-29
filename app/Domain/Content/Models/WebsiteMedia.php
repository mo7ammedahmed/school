<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An uploaded image or file in the website media library.
 *
 * Three pairs, not one: the library label, the alt text a screen reader reads
 * and the caption printed under the image are each stored in both languages.
 */
#[Fillable([
    'school_id',
    'name',
    'name_ar',
    'file_path',
    'mime_type',
    'size',
    'type',
    'dimensions',
    'alt_text',
    'alt_text_ar',
    'caption',
    'caption_ar',
    'metadata',
])]
class WebsiteMedia extends Model
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }
}
