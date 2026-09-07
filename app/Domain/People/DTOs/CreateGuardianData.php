<?php

declare(strict_types=1);

namespace App\Domain\People\DTOs;

final readonly class CreateGuardianData
{
    public function __construct(
        public int $school_id,
        public ?int $user_id,
        public string $first_name,
        public string $last_name,
        public string $relationship,
        public ?string $email,
        public ?string $phone,
        public ?string $national_id_number,
        public ?string $passport_number,
        public ?string $address,
        public ?string $occupation,
        public ?string $employer,
    ) {}

    public static function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'national_id_number' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
