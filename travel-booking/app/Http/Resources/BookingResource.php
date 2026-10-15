<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'booking_code' => $this->booking_code,
            'booking_type' => $this->booking_type,
            'booking_date' => $this->booking_date?->toIso8601String(),
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'expired_at' => $this->expired_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'hotel_booking' => new HotelBookingResource($this->whenLoaded('hotelBooking')),
            'flight_booking' => new FlightBookingResource($this->whenLoaded('flightBooking')),
            'passengers' => PassengerResource::collection($this->whenLoaded('passengers')),
            'payment' => $this->whenLoaded('payment'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
