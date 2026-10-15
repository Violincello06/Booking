<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flight_id' => ['required', 'integer', 'exists:flights,id'],
            'passenger_count' => ['required', 'integer', 'min:1', 'max:20'],
            'passengers' => ['required', 'array', 'min:1'],
            'passengers.*.full_name' => ['required', 'string', 'max:255'],
            'passengers.*.identity_number' => ['required', 'string', 'max:50'],
            'passengers.*.date_of_birth' => ['nullable', 'date'],
            'passengers.*.gender' => ['required', 'in:male,female'],
            'passengers.*.nationality' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi data pemesanan penerbangan gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
