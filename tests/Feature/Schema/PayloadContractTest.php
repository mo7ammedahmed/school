<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * A controller that accepts a value the row cannot hold loses it silently.
 *
 * That is how three modules ended up broken in the same way: announcements
 * posted `publish_date`, `expiry_date` and `is_active`; report cards posted
 * `grade`, `status` and `remarks`; fee structures posted a required `name`. None
 * of those columns existed, so the model dropped each one on the way in — the
 * operator filled a required field, the save succeeded, and the record lost the
 * value. The screens reading those fields rendered blanks, and nothing failed
 * loudly enough to notice.
 *
 * This walks every controller that writes an entity, takes the keys its
 * validation accepts, and checks them against the columns of the model being
 * written. A key passes when the column exists, or when the same code reads the
 * key back explicitly — `total_marks` is a form word that the `max_score` column
 * takes, and that mapping is legitimate.
 */
class PayloadContractTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, class-string<Model>>|null */
    private static ?array $models = null;

    public function test_every_validated_payload_key_can_be_stored_or_is_mapped(): void
    {
        $offenders = [];

        foreach ($this->controllerFiles() as $path) {
            $source = file_get_contents($path);

            $keys = $this->payloadKeys($source);

            if ($keys === []) {
                continue;
            }

            foreach ($this->modelsWritten($source) as $model) {
                $table = (new $model)->getTable();
                $columns = array_column(Schema::getColumns($table), 'name');

                foreach ($keys as $key) {
                    if (in_array($key, $columns, true)) {
                        continue;
                    }

                    // The file, or anything else in the app, reads the key back:
                    // the value is mapped onto a real column rather than dropped.
                    if ($this->isReadSomewhere($key)) {
                        continue;
                    }

                    $offenders[] = sprintf(
                        '%s accepts "%s" but writes %s (%s), which has no such column',
                        $this->shortPath($path),
                        $key,
                        class_basename($model),
                        $table,
                    );
                }
            }
        }

        $this->assertSame(
            [],
            array_values(array_unique($offenders)),
            "A validated field nothing can store is a field the operator fills for nothing:\n  ".implode("\n  ", array_unique($offenders)),
        );
    }

    /**
     * Keys that are not columns on purpose, because they describe something other
     * than a column: an upload, a key/value settings row, or an endpoint's own
     * payload. Each one is here because it was reviewed, not because the test
     * could not decide.
     *
     * @var list<string>
     */
    private const array NOT_A_COLUMN = [
        // File uploads: the value is a file, and the stored value is its path.
        'file', 'document', 'logo', 'favicon', 'photo', 'featured_image', 'attachment', 'import',
        // Settings are stored one row per key in `school_settings`, so the key is
        // data rather than a column. Validation is the only place they appear.
        'appearance', 'theme_config', 'light', 'dark', 'mode', 'grading_system', 'pass_mark',
        'rounding_method', 'include_extracurricular', 'school', 'excused_types', 'late_threshold_minutes',
        'default_locale', 'default_timezone', 'date_format', 'time_format', 'currency_symbol',
        'number_format', 'week_start', 'labels', 'preferences', 'allowed_ips', 'session_timeout',
        'max_login_attempts', 'password_min_length', 'password_expiry_days', 'password_require_uppercase',
        'password_require_numbers', 'password_require_symbols', 'current_password', 'new_password',
        // Notification and gateway switches, likewise key/value rows.
        'email_enrollment', 'email_attendance', 'email_exam', 'email_assignment', 'email_announcement',
        'gateways', 'auto_send', 'public_key', 'secret_key', 'webhook_secret', 'clear_secret_key',
        'clear_webhook_secret', 'providers', 'account_sid', 'auth_token', 'clear_api_key',
        'clear_mail_password', 'mail_driver', 'mail_encryption', 'mail_host', 'mail_port',
        'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
        // Translation configuration: same store, plus the provider payloads.
        'api_key', 'provider', 'model', 'base_url', 'auto_translate', 'source_locale', 'target_locale',
        'locales', 'limit', 'text', 'from', 'to',
        // Endpoint payloads that describe a query or a form, never a column.
        'filters', 'delivery', 'channels', 'errors', 'defaults', 'range', 'days', 'time', 'printed',
        'empty', 'timetable', 'application_ids', 'reviewer_id', 'priority', 'priorityLevels',
        'low', 'medium', 'high', 'urgent', 'table', 'source_column', 'source_value', 'column',
        'value', 'label', 'is_default', 'is_branch',
    ];

    /**
     * The payload keys a controller validates: its own `validate()` array literal
     * and the `rules()` of any form request it delegates to.
     *
     * @return list<string>
     */
    private function payloadKeys(string $source): array
    {
        $keys = [];

        // `$request->validate([ ... ])` up to the closing `]);`.
        if (preg_match_all('/validate\(\s*\[(.*?)\n\s*\]\s*\)/s', $source, $blocks)) {
            foreach ($blocks[1] as $block) {
                $keys = [...$keys, ...$this->keysIn($block)];
            }
        }

        // A form request's `rules()` body.
        foreach ($this->requiredRequests($source) as $request) {
            $requestPath = app_path('Http/Requests/'.$request.'.php');

            if (! is_file($requestPath)) {
                continue;
            }

            $requestSource = file_get_contents($requestPath);

            if (preg_match('/function rules\(\)[^{]*\{(.*?)\n    \}/s', $requestSource, $match)) {
                $keys = [...$keys, ...$this->keysIn($match[1])];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * The form request class names a controller's methods type-hint.
     *
     * @return list<string>
     */
    private function requiredRequests(string $source): array
    {
        $names = [];

        if (preg_match_all('/use\s+App\\\\Http\\\\Requests\\\\(\w+);/', $source, $matches)) {
            foreach ($matches[1] as $name) {
                // Only requests actually referenced in a signature: the imported
                // name, preceded by the namespace separator, before a variable.
                $typed = preg_quote('\\'.$name, '/').'\s+\$';

                if (preg_match('/'.$typed.'/m', $source) === 1) {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function keysIn(string $block): array
    {
        $keys = [];

        if (preg_match_all("/'([a-z0-9_]+)'\s*=>/i", $block, $matches)) {
            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Models the controller mass-assigns: `X::create(` / `X::update(` / `X::firstOrCreate(`.
     *
     * @return list<class-string<Model>>
     */
    private function modelsWritten(string $source): array
    {
        if (! preg_match_all('/([A-Z]\w+)::(?:create|update|firstOrCreate|updateOrCreate)\(/', $source, $matches)) {
            return [];
        }

        $models = [];

        foreach (array_unique($matches[1]) as $short) {
            $class = self::knownModels()[$short] ?? null;

            if ($class !== null && is_subclass_of($class, Model::class)) {
                $models[] = $class;
            }
        }

        return array_values(array_unique($models));
    }

    /**
     * Whether the application reads the key back by name.
     *
     * The corpus has every validation rule stripped out first, or the rule that
     * declares a key would be the proof that the key is used: `'category' =>` is
     * not a read, while `$request->string('reference')` and `$validated['total_marks']`
     * both are.
     */
    private function isReadSomewhere(string $key): bool
    {
        static $reads = null;

        if (in_array($key, self::NOT_A_COLUMN, true)) {
            return true;
        }

        if ($reads === null) {
            $reads = '';

            foreach (self::phpFiles(app_path()) as $file) {
                $source = file_get_contents($file);

                $source = preg_replace('/validate\(\s*\[.*?\n\s*\]\s*\)/s', '', $source) ?? $source;
                $source = preg_replace('/function rules\(\)[^{]*\{.*?\n    \}/s', '', $source) ?? $source;

                $reads .= $source;
            }
        }

        return str_contains($reads, "'{$key}'") || str_contains($reads, '"'.$key.'"');
    }

    /**
     * Short class name => fully qualified name, for every model in the app.
     *
     * @return array<string, class-string>
     */
    private static function knownModels(): array
    {
        if (self::$models !== null) {
            return self::$models;
        }

        $models = [];

        foreach (self::phpFiles(app_path()) as $file) {
            $source = file_get_contents($file);

            if (! preg_match('/^namespace\s+([^;]+);/m', $source, $namespace)) {
                continue;
            }

            if (! preg_match('/^class\s+(\w+)\s+extends\s+([\w\\\\]+)/m', $source, $class)) {
                continue;
            }

            $short = $class[1];

            // The app models are thin subclasses of the domain ones; either
            // spelling resolves to the same table.
            $models[$short] ??= $namespace[1].'\\'.$short;
        }

        return self::$models = $models;
    }

    /** @return list<string> */
    private function controllerFiles(): array
    {
        return self::phpFiles(app_path('Http/Controllers'));
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function shortPath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
