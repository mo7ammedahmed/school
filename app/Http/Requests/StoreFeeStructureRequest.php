<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A fee structure is an amount plus a description in both languages.
 *
 * The rules used to require a `name` field, which no column holds: the model
 * dropped it, so the operator filled in a required field and the saved record
 * lost it, leaving the list and detail screens blank. What is required is the
 * fee type, an amount, and at least one side of the bilingual description.
 */
class StoreFeeStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolId = (int) session('school_id');

        return [
            'description' => ['nullable', 'string', 'max:1000', 'required_without:description_ar'],
            'description_ar' => ['nullable', 'string', 'max:1000', 'required_without:description'],
            // Scoped to the school: an unscoped `exists` would accept another
            // school's fee type or grade level id.
            'fee_type_id' => ['required', Rule::exists('fee_types', 'id')->where('school_id', $schoolId)],
            'grade_level_id' => ['nullable', Rule::exists('grade_levels', 'id')->where('school_id', $schoolId)],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
