<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_count' => ['required', 'integer', 'min:1', 'max:20'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:50'],
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.full_name' => ['required', 'string', 'max:255'],
            'guests.*.identity_number' => ['nullable', 'string', 'max:50'],
            'guests.*.date_of_birth' => ['nullable', 'date'],
            'guests.*.gender' => ['required', 'in:male,female'],
            'guests.*.nationality' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi data pemesanan hotel gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
