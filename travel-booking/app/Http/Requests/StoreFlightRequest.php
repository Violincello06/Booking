<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline_id' => ['required', 'exists:airlines,id'],
            'departure_airport_id' => ['required', 'exists:airports,id'],
            'arrival_airport_id' => ['required', 'exists:airports,id', 'different:departure_airport_id'],
            'flight_number' => ['required', 'string', 'max:50'],
            'departure_time' => ['required', 'date', 'after:now'],
            'arrival_time' => ['required', 'date', 'after:departure_time'],
            'duration' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'total_seats' => ['required', 'integer', 'min:1'],
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
