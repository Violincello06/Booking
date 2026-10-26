<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'booking_id' => $this->booking_id,
            'booking_code' => $this->booking?->booking_code,
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'transaction_reference' => $this->transaction_reference,
            'payment_instructions' => $this->getPaymentInstructions(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Generate simulated payment instructions depending on method.
     */
    protected function getPaymentInstructions(): array
    {
        $code = $this->payment_code;
        $numPart = preg_replace('/[^0-9]/', '', $code);

        return match ($this->payment_method) {
            'bca_va' => [
                'type' => 'virtual_account',
                'bank' => 'BCA',
                'va_number' => '80777' . str_pad($numPart, 10, '0', STR_PAD_LEFT),
                'instruction' => 'Transfer via BCA Mobile / ATM ke nomor Virtual Account di atas.',
            ],
            'mandiri_va' => [
                'type' => 'virtual_account',
                'bank' => 'Mandiri',
                'va_number' => '88908' . str_pad($numPart, 10, '0', STR_PAD_LEFT),
                'instruction' => 'Pilih Bayar/Beli -> Multi Payment -> Masukkan nomor Virtual Account.',
            ],
            'bni_va' => [
                'type' => 'virtual_account',
                'bank' => 'BNI',
                'va_number' => '98888' . str_pad($numPart, 10, '0', STR_PAD_LEFT),
                'instruction' => 'Transfer via BNI Mobile Banking / ATM.',
            ],
            'bri_va' => [
                'type' => 'virtual_account',
                'bank' => 'BRI',
                'va_number' => '12800' . str_pad($numPart, 10, '0', STR_PAD_LEFT),
                'instruction' => 'Pilih Pembayaran -> BRIVA -> Masukkan nomor BRIVA.',
            ],
            'gopay' => [
                'type' => 'ewallet',
                'provider' => 'GoPay',
                'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=GOPAY-{$code}",
                'instruction' => 'Buka aplikasi Gojek / GoPay dan scan QR Code atau konfirmasi notifikasi.',
            ],
            'ovo' => [
                'type' => 'ewallet',
                'provider' => 'OVO',
                'instruction' => 'Buka aplikasi OVO dan selesaikan pembayaran pada notifikasi push.',
            ],
            'dana' => [
                'type' => 'ewallet',
                'provider' => 'DANA',
                'instruction' => 'Buka aplikasi DANA dan klik Bayar.',
            ],
            'qris' => [
                'type' => 'qris',
                'qr_string' => "00020101021226500016ID.CO.QRIS.WWW0118{$code}5204581253033605802ID",
                'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=QRIS-{$code}",
                'instruction' => 'Scan QRIS menggunakan aplikasi perbankan atau e-wallet apa saja (BCA, Mandiri, GoPay, OVO, ShopeePay).',
            ],
            'credit_card' => [
                'type' => 'card',
                'provider' => 'Visa / Mastercard',
                'instruction' => 'Masukkan 16 digit kartu kredit dan kode OTP yang dikirimkan bank.',
            ],
            default => [
                'type' => 'general',
                'instruction' => 'Selesaikan pembayaran sebelum batas waktu kedaluwarsa.',
            ],
        };
    }
}
