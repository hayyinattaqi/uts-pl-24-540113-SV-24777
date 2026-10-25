<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockInRequest extends FormRequest
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
        return [
            'medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'batch_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('batches', 'batch_number')->where('medicine_id', $this->input('medicine_id')),
            ],
            'expiry_date' => ['required', 'date_format:Y-m-d', 'after:today'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'medicine_id.required' => 'Obat wajib dipilih.',
            'medicine_id.integer' => 'Obat tidak valid.',
            'medicine_id.exists' => 'Obat tidak ditemukan.',
            'batch_number.required' => 'Nomor batch wajib diisi.',
            'batch_number.max' => 'Nomor batch maksimal 50 karakter.',
            'batch_number.unique' => 'Nomor batch sudah ada untuk obat ini.',
            'expiry_date.required' => 'Tanggal kedaluwarsa wajib diisi.',
            'expiry_date.date_format' => 'Tanggal kedaluwarsa harus berformat YYYY-MM-DD.',
            'expiry_date.after' => 'Tanggal kedaluwarsa harus setelah hari ini.',
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.integer' => 'Jumlah harus berupa bilangan bulat.',
            'quantity.min' => 'Jumlah minimal 1.',
            'notes.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
