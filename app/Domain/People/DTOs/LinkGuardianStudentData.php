<?php

declare(strict_types=1);

namespace App\Domain\People\DTOs;

final readonly class LinkGuardianStudentData
{
    public function __construct(
        public int $school_id,
        public int $guardian_id,
        public int $student_id,
        public string $relationship,
        public bool $is_primary,
        public ?string $notes,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'guardian_id' => ['required', 'integer', 'exists:guardians,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'relationship' => ['required', 'string', 'max:100'],
            'is_primary' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
