<?php

declare(strict_types=1);

namespace App\Domain\Localization\Models;

use Illuminate\Database\Eloquent\Model;

class InterfaceTranslation extends Model
{
    protected $fillable = ['source_hash', 'english', 'arabic', 'updated_by'];

    public static function hashSource(string $english): string
    {
        return hash('sha256', trim($english));
    }

    public static function catalogVersion(): string
    {
        $entries = static::query()
            ->orderBy('source_hash')
            ->get(['source_hash', 'arabic'])
            ->map(static fn (self $entry): string => $entry->source_hash.':'.hash('sha256', $entry->arabic))
            ->implode('|');

        return hash('sha256', $entries);
    }
}