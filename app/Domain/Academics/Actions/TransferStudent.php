<?php

declare(strict_types=1);

namespace App\Domain\Academics\Actions;

use App\Domain\Academics\Models\Enrollment;
use App\Domain\Academics\Models\Section;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class TransferStudent
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Enrollment $enrollment, Section $targetSection, ?string $transferDate = null): Enrollment
    {
        if ($enrollment->school_id !== $this->school->id) {
            throw new Exception('Enrollment does not belong to this school.');
        }

        if ($targetSection->school_id !== $this->school->id) {
            throw new Exception('Target section does not belong to this school.');
        }

        if ($enrollment->academic_year_id !== $targetSection->academic_year_id) {
            throw new Exception('Cannot transfer student to a section from a different academic year.');
        }

        if ($enrollment->status === 'withdrawn') {
            throw new Exception('Cannot transfer a withdrawn student.');
        }

        if ($targetSection->current_count >= $targetSection->capacity) {
            throw new Exception('Target section has reached its maximum capacity.');
        }

        return DB::transaction(function () use ($enrollment, $targetSection, $transferDate) {
            $sourceSection = $enrollment->section;
            $enrollment->update([
                'section_id' => $targetSection->id,
                'notes' => $enrollment->notes
                    ? $enrollment->notes."\nTransferred from section {$sourceSection->name} on ".($transferDate ?? now()->toDateString())
                    : "Transferred from section {$sourceSection->name} on ".($transferDate ?? now()->toDateString()),
            ]);

            if ($sourceSection->current_count > 0) {
                $sourceSection->decrement('current_count');
            }
            $targetSection->increment('current_count');

            return $enrollment->fresh();
        });
    }
}
