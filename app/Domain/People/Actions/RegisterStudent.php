<?php

declare(strict_types=1);

namespace App\Domain\People\Actions;

use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterStudent
{
    public function __construct(
        private readonly School $school,
    ) {}

    public function execute(array $data): Student
    {
        $requiredFields = ['first_name', 'last_name', 'date_of_birth', 'gender'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required to register a student.");
            }
        }

        $userData = [
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'] ?? Str::lower($data['first_name'].'.'.$data['last_name'].'@student.local'),
            'password' => bcrypt(Str::random(16)),
        ];

        DB::transaction(function () use ($data, $userData) {
            $user = User::create($userData);

            return Student::create([
                'school_id' => $this->school->id,
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'student_id_number' => $data['student_id_number'] ?? $this->generateStudentIdNumber(),
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality'] ?? null,
                'national_id_number' => $data['national_id_number'] ?? null,
                'passport_number' => $data['passport_number'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'enrollment_date' => $data['enrollment_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'active',
                'medical_notes' => $data['medical_notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);
        });

        return $student->load('user');
    }

    private function generateStudentIdNumber(): string
    {
        $prefix = 'STU-'.date('Y').'-';
        $lastStudent = Student::where('student_id_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) str_replace($prefix, '', $lastStudent->student_id_number);

            return $prefix.str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
        }

        return $prefix.str_pad('1', 6, '0', STR_PAD_LEFT);
    }
}
