<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'fee_type_id' => ['required', 'exists:fee_types,id'],
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
