<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DestinationResource extends JsonResource
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
            'name' => $this->name,
            'country' => $this->country,
            'province' => $this->province,
            'city' => $this->city,
            'description' => $this->description,
            'image' => $this->image,
            'status' => $this->status,
            'hotels_count' => $this->whenCounted('hotels'),
            'hotels' => HotelResource::collection($this->whenLoaded('hotels')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
