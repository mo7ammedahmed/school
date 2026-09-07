<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Domain\Academics\Models\Offering;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'offering_id',
    'title',
    'description',
    'file_path',
    'file_type',
    'file_size',
    'is_published',
])]
class Material extends Model
{
    use SoftDeletes;

    /** Subject/section surface the owning offering; upload time mirrors creation time. */
    protected $appends = ['subject', 'section', 'uploaded_at'];

    public function getSubjectAttribute()
    {
        return $this->offering?->subject;
    }

    public function getSectionAttribute()
    {
        return $this->offering?->section;
    }

    public function getUploadedAtAttribute()
    {
        return $this->created_at?->toDateTimeString();
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }
}
