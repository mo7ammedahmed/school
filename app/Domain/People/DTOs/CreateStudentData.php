<?php

declare(strict_types=1);

namespace App\Domain\People\DTOs;

final readonly class CreateStudentData
{
    public function __construct(
        public int $school_id,
        public ?int $user_id,
        public string $first_name,
        public string $last_name,
        public string $student_id_number,
        public string $date_of_birth,
        public string $gender,
        public ?string $nationality,
        public ?string $national_id_number,
        public ?string $passport_number,
        public ?string $address,
        public ?string $phone,
        public ?string $email,
        public string $enrollment_date,
        public string $status,
        public ?string $medical_notes,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'student_id_number' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'date_format:Y-m-d'],
            'gender' => ['required', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'national_id_number' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'enrollment_date' => ['required', 'date', 'date_format:Y-m-d'],
            'status' => ['required', 'string', 'max:50'],
            'medical_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
