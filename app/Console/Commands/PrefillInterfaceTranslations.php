<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Localization\Exceptions\TranslationFailed;
use App\Domain\Localization\Models\InterfaceTranslation;
use App\Domain\Localization\Services\TranslationService;
use App\Domain\Schools\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Buys the interface dictionary in one sitting.
 *
 * The browser can only ask about strings it has reached, so a screen nobody has
 * opened yet is still English the first time it is opened. Running this against
 * the extracted string list translates the whole application up front, which is
 * what turns "Arabic pages that fill in as you browse" into "Arabic pages".
 *
 * It is safe to re-run: a string already in the dictionary is skipped, so a
 * second run only pays for what has been added to the app since the first.
 */
class PrefillInterfaceTranslations extends Command
{
    /** The column the English side is stored in. */
    private const int MAX_STRING = 300;

    protected $signature = 'translations:prefill
        {--manifest= : The extracted string list (default storage/app/interface-strings.json)}
        {--school= : School whose translation provider to use}
        {--limit=0 : Stop after this many new strings (0 means no limit)}
        {--seconds=120 : Spend at most this long talking to the provider}
        {--dry-run : Report what is missing without calling the provider}';

    protected $description = 'Translates the app\'s English interface strings into the shared Arabic dictionary';

    public function handle(TranslationService $translations): int
    {
        $manifest = (string) ($this->option('manifest') ?: storage_path('app/interface-strings.json'));

        if (! File::exists($manifest)) {
            $this->components->error("No string list at {$manifest}.");

            $this->components->info('Create it first: node scripts/extract-interface-strings.mjs');

            return self::FAILURE;
        }

        $strings = $this->readManifest($manifest);

        if ($strings === []) {
            $this->components->warn('The string list is empty, so there is nothing to translate.');

            return self::SUCCESS;
        }

        $missing = $this->missing($strings);

        $this->components->info(sprintf(
            '%d interface strings in the list, %d already translated, %d to go.',
            count($strings),
            count($strings) - count($missing),
            count($missing),
        ));

        if ($missing === []) {
            return self::SUCCESS;
        }

        if ((bool) $this->option('dry-run')) {
            $this->newLine();
            $this->components->info('Nothing was translated — this was a dry run. The first few waiting:');

            foreach (array_slice($missing, 0, 15) as $string) {
                $this->line('  '.$string);
            }

            $this->newLine();
            $this->components->info('Run again without --dry-run to translate them.');

            return self::SUCCESS;
        }

        $school = $this->school();

        if ($school === null) {
            $this->components->error('No school exists to take a translation provider from.');

            return self::FAILURE;
        }

        if (! $translations->autoTranslateEnabled($school)) {
            $this->components->error(sprintf(
                'School #%d has no automatic translation configured. Add a provider key in Settings → Translations first.',
                $school->id,
            ));

            return self::FAILURE;
        }

        return $this->translate($translations, $missing, $school);
    }

    /**
     * @return list<string>
     */
    private function readManifest(string $manifest): array
    {
        $decoded = json_decode((string) File::get($manifest), true);
        $raw = is_array($decoded) && isset($decoded['strings']) ? $decoded['strings'] : $decoded;

        if (! is_array($raw)) {
            return [];
        }

        $strings = [];

        foreach ($raw as $value) {
            if (! is_string($value)) {
                continue;
            }

            $string = trim($value);

            if ($string === '' || mb_strlen($string) > self::MAX_STRING) {
                continue;
            }

            $strings[$string] = true;
        }

        return array_keys($strings);
    }

    /**
     * The strings the dictionary does not hold yet.
     *
     * @param  list<string>  $strings
     * @return list<string>
     */
    private function missing(array $strings): array
    {
        $hashes = array_map(InterfaceTranslation::hashSource(...), $strings);

        $known = InterfaceTranslation::query()
            ->whereIn('source_hash', $hashes)
            ->pluck('source_hash')
            ->all();

        $known = array_flip($known);

        return array_values(array_filter(
            $strings,
            static fn (string $string): bool => ! isset($known[InterfaceTranslation::hashSource($string)]),
        ));
    }

    private function school(): ?School
    {
        $id = $this->option('school');

        if ($id !== null) {
            return School::query()->find((int) $id);
        }

        return School::query()->orderBy('id')->first();
    }

    /**
     * @param  list<string>  $missing
     */
    private function translate(TranslationService $translations, array $missing, School $school): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $deadline = microtime(true) + max(1.0, (float) $this->option('seconds'));
        $done = 0;
        $failed = 0;
        $stopped = false;

        $this->newLine();
        $this->components->info(sprintf('Translating with school #%d\'s provider…', $school->id));

        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, count($missing)) : count($missing));
        $bar->start();

        foreach ($missing as $string) {
            if ($limit > 0 && $done >= $limit) {
                break;
            }

            if (microtime(true) >= $deadline) {
                $stopped = true;

                break;
            }

            try {
                $arabic = $translations->translate($string, 'en', 'ar', $school);
            } catch (TranslationFailed $failure) {
                $failed++;

                $this->newLine();
                $this->components->warn('Stopping: '.$failure->getMessage());

                if ($failure->statusCode === 429) {
                    $stopped = true;
                }

                break;
            } catch (Throwable $error) {
                $failed++;

                $this->newLine();
                $this->components->warn(sprintf('Skipped "%s": %s', $string, $error->getMessage()));

                continue;
            }

            $arabic = trim($arabic);

            // A proper noun comes back unchanged; storing it would only make the
            // dictionary bigger without making a screen any more Arabic.
            if ($arabic === '' || $arabic === $string) {
                $failed++;
                $bar->advance();

                continue;
            }

            InterfaceTranslation::query()->firstOrCreate(
                ['source_hash' => InterfaceTranslation::hashSource($string)],
                ['english' => $string, 'arabic' => $arabic],
            );

            $done++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $remaining = count($missing) - $done - $failed;

        $this->components->info(sprintf(
            'Added %d translations; %d were left alone, %d still to go.%s',
            $done,
            $failed,
            max(0, $remaining),
            $stopped ? ' Stopped early — run again to continue.' : '',
        ));

        return self::SUCCESS;
    }
}
