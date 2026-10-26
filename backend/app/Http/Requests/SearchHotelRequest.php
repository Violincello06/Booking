<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'star_rating' => ['nullable', 'integer', 'between:1,5'],
            'min_rating' => ['nullable', 'integer', 'between:1,5'],
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
            'facility' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'in:price_asc,price_desc,rating_desc,rating_asc,name_asc,name_desc,latest'],
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
