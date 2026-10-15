<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline_id' => ['sometimes', 'required', 'exists:airlines,id'],
            'departure_airport_id' => ['sometimes', 'required', 'exists:airports,id'],
            'arrival_airport_id' => ['sometimes', 'required', 'exists:airports,id'],
            'flight_number' => ['sometimes', 'required', 'string', 'max:50'],
            'departure_time' => ['sometimes', 'required', 'date'],
            'arrival_time' => ['sometimes', 'required', 'date'],
            'duration' => ['sometimes', 'required', 'integer', 'min:1'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'total_seats' => ['sometimes', 'required', 'integer', 'min:1'],
            'available_seats' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:scheduled,delayed,departed,arrived,cancelled'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
