<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'departure_airport_id' => ['nullable', 'integer', 'exists:airports,id'],
            'arrival_airport_id' => ['nullable', 'integer', 'exists:airports,id', 'different:departure_airport_id'],
            'departure_airport_code' => ['nullable', 'string', 'size:3'],
            'arrival_airport_code' => ['nullable', 'string', 'size:3', 'different:departure_airport_code'],
            'departure_city' => ['nullable', 'string', 'max:255'],
            'arrival_city' => ['nullable', 'string', 'max:255'],
            'departure_date' => ['nullable', 'date'],
            'airline_id' => ['nullable'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->filled('min_price') && (float) $value < (float) $this->input('min_price')) {
                        $fail('The max price field must be greater than or equal to min price.');
                    }
                },
            ],
            'min_seats' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'in:price_asc,price_desc,departure_time_asc,departure_time_desc,duration_asc,duration_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi parameter pencarian gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
