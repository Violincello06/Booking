<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'booking_code' => ['required', 'string', 'exists:bookings,booking_code'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'booking_code.required' => 'Kode booking wajib diisi.',
            'booking_code.exists' => 'Kode booking tidak ditemukan dalam sistem.',
            'rating.required' => 'Nilai rating wajib diberikan.',
            'rating.integer' => 'Nilai rating harus berupa angka bulat.',
            'rating.min' => 'Nilai rating minimal adalah 1 bintang.',
            'rating.max' => 'Nilai rating maksimal adalah 5 bintang.',
            'comment.max' => 'Komentar ulasan tidak boleh melebihi 1000 karakter.',
        ];
    }
}
