<?php

declare(strict_types=1);

namespace App\Validation;

use Illuminate\Validation\Rules\File;

/**
 * What may be attached to a school record.
 *
 * `'file'` asserts that a file arrived. It says nothing about what it is, so
 * `'file' => 'file|max:10240'` accepts `shell.php` exactly as readily as
 * `lesson.docx`. Where the file then goes decides how much that matters: these
 * uploads used to be written to the `public` disk, which `storage:link` exposes
 * inside the document root, so "accepts anything" was "accepts anything the web
 * server will run".
 *
 * The list is an allowlist rather than a denylist of dangerous extensions. A
 * denylist of `.php`, `.phtml`, `.phar` is a list that has to be extended every
 * time somebody learns of another way to get a file executed, and it says
 * nothing about `.html` — an HTML file on the application's own origin is a
 * phishing page that satisfies every same-origin check a browser makes.
 *
 * SVG is excluded for the same reason: an SVG is a document that can carry
 * script. Where the branding logo is genuinely a picture, `image` with an
 * explicit list is the rule — see `AppearanceSettingsController`.
 *
 * Only office formats and images are listed, because those are what a school
 * attaches to a material, a document or a submission. Nothing here is
 * negotiated at runtime: widening this list is a decision someone should make
 * deliberately, having decided the file will never be served from the web root.
 */
final class AllowedAttachment
{
    /**
     * Extensions a school legitimately attaches.
     *
     * @var list<string>
     */
    public const EXTENSIONS = [
        'pdf',
        'doc', 'docx',
        'xls', 'xlsx',
        'ppt', 'pptx',
        'odt', 'ods', 'odp',
        'rtf',
        'txt', 'csv',
        'png', 'jpg', 'jpeg', 'webp',
    ];

    public const MAX_KILOBYTES = 10240;

    /**
     * The rule, with the size bound this codebase already used.
     *
     * `types()` checks the extension *and* the MIME type reported by the file's
     * own contents, so a `.docx` that is really a PHP script is refused on the
     * second check as well. It also refuses `report.pdf.php`, which a rule
     * reading only the last extension segment would wave through.
     */
    public static function rule(): File
    {
        return File::types(self::EXTENSIONS)->max(self::MAX_KILOBYTES);
    }
}
