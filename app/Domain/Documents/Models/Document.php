<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'document_category_id',
    'title',
    'title_ar',
    'file_path',
    'file_type',
    'file_size',
    'classification',
    'linked_entity_type',
    'linked_entity_id',
    'uploaded_by',
    'description',
    'description_ar',
])]
class Document extends Model
{
    use BelongsToSchool, SoftDeletes;

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function documentCategory(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
