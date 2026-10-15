<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
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
            'destination_id' => $this->destination_id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'star_rating' => $this->star_rating,
            'contact_phone' => $this->contact_phone,
            'min_price' => $this->rooms_min_price_per_night !== null ? (float) $this->rooms_min_price_per_night : null,
            'max_price' => $this->rooms_max_price_per_night !== null ? (float) $this->rooms_max_price_per_night : null,
            'status' => $this->status,
            'destination' => new DestinationResource($this->whenLoaded('destination')),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'reviews_count' => $this->whenCounted('reviews'),
            'average_rating' => $this->whenNotNull($this->whenLoaded('reviews', fn () => round($this->reviews->avg('rating'), 1))),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
