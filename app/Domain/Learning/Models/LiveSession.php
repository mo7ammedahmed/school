<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\Academics\Models\Offering;
use App\Domain\Schools\Models\School;
use App\Domain\Schools\Support\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One live lesson, from "draft" to "ended with a recording".
 *
 * `stream_key` is the random MediaMTX path. Access additionally requires an
 * action-bound credential checked against current membership and enrollment.
 *
 * `status` is one of {@see self::STATUS_DRAFT} (created, not started),
 * {@see self::STATUS_LIVE} (teacher publishing) or {@see self::STATUS_ENDED}.
 * After the end, `recording_status` tracks the finalize job:
 * `pending` → `ready` (or `failed`), with `material_id` pointing at the lesson
 * material the job published.
 */
#[Fillable([
    'school_id',
    'offering_id',
    'started_by',
    'title',
    'title_ar',
    'status',
    'stream_key',
    'started_at',
    'ended_at',
    'duration_seconds',
    'viewer_peak',
    'recording_status',
    'recording_path',
    'recording_size',
    'material_id',
])]
class LiveSession extends Model
{
    use BelongsToSchool, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_LIVE = 'live';

    public const STATUS_ENDED = 'ended';

    public const RECORDING_PENDING = 'pending';

    public const RECORDING_PROCESSING = 'processing';

    public const RECORDING_READY = 'ready';

    public const RECORDING_FAILED = 'failed';

    /** Sessions the teacher is publishing right now. */
    #[Scope]
    protected function live(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LIVE);
    }

    /** Sessions whose recording has not been published as a material yet. */
    #[Scope]
    protected function awaitingRecording(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ENDED)
            ->whereIn('recording_status', [self::RECORDING_PENDING, self::RECORDING_PROCESSING]);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isEnded(): bool
    {
        return $this->status === self::STATUS_ENDED;
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'viewer_peak' => 'integer',
            'recording_size' => 'integer',
        ];
    }
}
