<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelBookingResource extends JsonResource
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
            'booking_id' => $this->booking_id,
            'room_id' => $this->room_id,
            'check_in' => $this->check_in?->format('Y-m-d') ?? $this->check_in,
            'check_out' => $this->check_out?->format('Y-m-d') ?? $this->check_out,
            'guest_count' => $this->guest_count,
            'room_count' => $this->room_count,
            'price_per_night' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            'room' => new RoomResource($this->whenLoaded('room')),
        ];
    }
}
