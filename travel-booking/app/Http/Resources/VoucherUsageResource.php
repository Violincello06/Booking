<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherUsageResource extends JsonResource
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
            'voucher_id' => $this->voucher_id,
            'voucher_code' => $this->voucher?->code,
            'voucher_name' => $this->voucher?->name,
            'booking_id' => $this->booking_id,
            'booking_code' => $this->booking?->booking_code,
            'discount_amount' => (float) $this->discount_amount,
            'used_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
