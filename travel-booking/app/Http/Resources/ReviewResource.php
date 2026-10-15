<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'hotel' => [
                'id' => $this->hotel?->id,
                'name' => $this->hotel?->name,
                'star_rating' => $this->hotel?->star_rating,
            ],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'booking_code' => $this->booking?->booking_code,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
