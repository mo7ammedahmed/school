<?php

declare(strict_types=1);

namespace App\Domain\Schools\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\OrganizationFactory;

#[Fillable([
    'name',
    'slug',
    'email',
    'phone',
    'address',
    'logo_path',
    'metadata',
])]
#[UseFactory(OrganizationFactory::class)]
class Organization extends Model
{
    use SoftDeletes, HasFactory;


    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
