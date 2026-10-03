<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Localization\Models\InterfaceTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * The Arabic interface dictionary, as data.
 *
 * The screens are written in English, and Arabic is filled in from a shared
 * dictionary (see InterfaceCatalog). Automatic translation can only buy a
 * string once somebody has reached the screen that shows it — and only while a
 * provider key is configured — so an Arabic page used to keep its English
 * words in it. This ships the whole dictionary instead: one reviewed pair per
 * interface string, written down in database/seeders/data.
 *
 * Only missing entries are written. Wording a school has edited, or a provider
 * has answered, is left exactly as it is, so running this after an upgrade
 * tops the dictionary up without undoing anybody's work. Re-running it with
 * nothing missing adds nothing.
 */
class InterfaceTranslationSeeder extends Seeder
{
    /** The hand-written English-to-Arabic pairs, keyed by the English string. */
    private const string DATA_FILE = 'seeders/data/interface-translations-ar.json';

    public function run(): void
    {
        $pairs = $this->pairs();
        $known = InterfaceTranslation::query()->pluck('source_hash')->flip();
        $now = now();
        $rows = [];

        foreach ($pairs as $english => $arabic) {
            $hash = InterfaceTranslation::hashSource($english);

            if ($known->has($hash)) {
                continue;
            }

            $rows[] = [
                'source_hash' => $hash,
                'english' => $english,
                'arabic' => $arabic,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            InterfaceTranslation::query()->insert($chunk);
        }

        $this->command?->info(sprintf(
            'Interface translations: %d added, %d already present.',
            count($rows),
            count($pairs) - count($rows),
        ));
    }

    /**
     * The pair file, with whitespace around either side removed.
     *
     * @return array<string, string>
     */
    private function pairs(): array
    {
        $path = database_path(self::DATA_FILE);
        $decoded = json_decode((string) File::get($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("The Arabic interface dictionary at {$path} is not readable JSON.");
        }

        $pairs = [];

        foreach ($decoded as $english => $arabic) {
            if (! is_string($english) || ! is_string($arabic)) {
                continue;
            }

            $english = trim($english);
            $arabic = trim($arabic);

            if ($english === '' || $arabic === '') {
                continue;
            }

            $pairs[$english] = $arabic;
        }

        return $pairs;
    }
}
