<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'invoice_id' => ['required', 'exists:invoices,id'],
            'payment_number' => ['nullable', 'string', 'max:255', 'unique:payments,payment_number'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:3', Rule::in(['SAR', 'USD', 'AED', 'KWD', 'QAR', 'BHD', 'OMR', 'EGP', 'JOD'])],
            'payment_method' => ['required', 'string', Rule::in(['cash', 'bank_transfer', 'credit_card', 'moyasar', 'hyperpay', 'stripe', 'other'])],
            'status' => ['nullable', 'string', Rule::in(['pending', 'paid', 'failed', 'refunded', 'voided', 'captured', 'settled'])],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
