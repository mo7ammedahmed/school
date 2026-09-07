<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day' => ['required', 'string', 'in:Sunday,Monday,Tuesday,Wednesday,Thursday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'section_id' => ['required', 'exists:sections,id'],
            'teacher_id' => ['required', 'exists:teacher_profiles,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ];
    }
}
