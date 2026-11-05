<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $medicine = $this->route('medicine');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('medicines', 'code')->ignore($medicine),
            ],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * Catat log terstruktur saat validasi data obat gagal.
     */
    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Validasi data obat gagal.', [
            'action' => 'validate_medicine_input',
            'user_id' => $this->user()?->id ?? 'guest',
            'ip' => $this->ip(),
            'failed_fields' => array_keys($validator->errors()->toArray()),
            'reason' => 'Input data obat tidak memenuhi aturan validasi yang ditentukan.',
        ]);

        parent::failedValidation($validator);
    }
}
