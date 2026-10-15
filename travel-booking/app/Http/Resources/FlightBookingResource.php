<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightBookingResource extends JsonResource
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
            'flight_id' => $this->flight_id,
            'passenger_count' => $this->passenger_count,
            'price_per_ticket' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            'flight' => new FlightResource($this->whenLoaded('flight')),
        ];
    }
}
