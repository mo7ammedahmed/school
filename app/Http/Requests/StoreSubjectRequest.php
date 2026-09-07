<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'teacher_id' => ['required', 'exists:teacher_profiles,id'],
        ];
    }
}
