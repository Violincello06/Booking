<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'minimum_transaction' => $this->minimum_transaction,
            'maximum_discount' => $this->maximum_discount,
            'quota' => $this->quota,
            'used_quota' => $this->used_quota,
            'remaining_quota' => max(0, $this->quota - $this->used_quota),
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'status' => $this->status,
            'is_valid' => $this->status === 'active' && now()->between($this->start_date, $this->end_date) && ($this->quota > $this->used_quota),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
