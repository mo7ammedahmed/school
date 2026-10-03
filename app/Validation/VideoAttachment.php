<?php

declare(strict_types=1);

namespace App\Validation;

use Illuminate\Validation\Rules\File;

/**
 * A lesson video a teacher records or uploads.
 *
 * Deliberately separate from {@see AllowedAttachment}: a document is capped at
 * 10 MB and a video at 512 MB, and one shared rule would either reject every
 * real lesson or loosen the ceiling for a PDF. Both rules still check the
 * extension *and* the MIME type reported by the file's contents, so a `.mp4`
 * that is really something else is refused.
 *
 * 512 MB is the PHP default's neighbourhood (`post_max_size`), not a product
 * limit: the Docker image and nginx are configured to match it, and a file that
 * large is still a single request today. Resumable uploads are the follow-up
 * when teachers start recording longer than an hour.
 */
final class VideoAttachment
{
    /**
     * @var list<string>
     */
    public const EXTENSIONS = ['mp4', 'webm', 'mov', 'mkv'];

    public const MAX_KILOBYTES = 524288;

    public static function rule(): File
    {
        return File::types(self::EXTENSIONS)->max(self::MAX_KILOBYTES);
    }
}
