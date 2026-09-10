<?php

declare(strict_types=1);

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use Exception;
use Illuminate\Support\Facades\DB;

class LinkGuardianToStudent
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(Guardian $guardian, Student $student, array $relationshipData = []): GuardianRelationship
    {
        if ($guardian->school_id !== $this->school->id) {
            throw new Exception('Guardian does not belong to this school.');
        }

        if ($student->school_id !== $this->school->id) {
            throw new Exception('Student does not belong to this school.');
        }

        $existing = GuardianRelationship::where('guardian_id', $guardian->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            throw new Exception('This guardian is already linked to this student.');
        }

        $data = [
            'school_id' => $this->school->id,
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'is_primary' => $relationshipData['is_primary'] ?? false,
            'is_financial_guardian' => $relationshipData['is_financial_guardian'] ?? false,
            'can_pickup' => $relationshipData['can_pickup'] ?? false,
            'notes' => $relationshipData['notes'] ?? null,
        ];

        return DB::transaction(fn () => GuardianRelationship::create($data));
    }
}
