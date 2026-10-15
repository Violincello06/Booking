<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightResource extends JsonResource
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
            'airline_id' => $this->airline_id,
            'departure_airport_id' => $this->departure_airport_id,
            'arrival_airport_id' => $this->arrival_airport_id,
            'flight_number' => $this->flight_number,
            'departure_time' => $this->departure_time?->toIso8601String(),
            'arrival_time' => $this->arrival_time?->toIso8601String(),
            'duration' => $this->duration,
            'price' => (float) $this->price,
            'total_seats' => $this->total_seats,
            'available_seats' => $this->available_seats,
            'status' => $this->status,
            'airline' => new AirlineResource($this->whenLoaded('airline')),
            'departure_airport' => new AirportResource($this->whenLoaded('departureAirport')),
            'arrival_airport' => new AirportResource($this->whenLoaded('arrivalAirport')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
