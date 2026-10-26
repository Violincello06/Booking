<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_code' => ['required', 'string', 'exists:bookings,booking_code'],
            'payment_method' => [
                'required',
                'string',
                'in:bca_va,mandiri_va,bni_va,bri_va,gopay,ovo,dana,qris,credit_card',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi data pembayaran gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}
