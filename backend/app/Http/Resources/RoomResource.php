<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'hotel_id' => $this->hotel_id,
            'room_type' => $this->room_type,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'price_per_night' => (float) $this->price_per_night,
            'total_rooms' => $this->total_rooms,
            'available_rooms' => $this->available_rooms,
            'facilities' => $this->facilities ?? [],
            'image' => $this->image,
            'status' => $this->status,
            'hotel' => new HotelResource($this->whenLoaded('hotel')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
