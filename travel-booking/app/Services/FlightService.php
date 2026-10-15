<?php

namespace App\Services;

use App\Models\Flight;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FlightService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->search([], $perPage);
    }

    public function search(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Flight::query()
            ->with(['airline', 'departureAirport', 'arrivalAirport']);

        // Default or specific status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Keyword query
        if (! empty($filters['query'])) {
            $keyword = trim($filters['query']);
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('flight_number', 'like', "%{$keyword}%")
                  ->orWhereHas('airline', function (Builder $air) use ($keyword) {
                      $air->where('name', 'like', "%{$keyword}%")
                          ->orWhere('code', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('departureAirport', function (Builder $dept) use ($keyword) {
                      $dept->where('name', 'like', "%{$keyword}%")
                           ->orWhere('code', 'like', "%{$keyword}%")
                           ->orWhere('city', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('arrivalAirport', function (Builder $arr) use ($keyword) {
                      $arr->where('name', 'like', "%{$keyword}%")
                          ->orWhere('code', 'like', "%{$keyword}%")
                          ->orWhere('city', 'like', "%{$keyword}%");
                  });
            });
        }

        // Departure Airport Filter (by ID or Code)
        if (! empty($filters['departure_airport_id'])) {
            $query->where('departure_airport_id', $filters['departure_airport_id']);
        } elseif (! empty($filters['departure_airport_code'])) {
            $code = strtoupper(trim($filters['departure_airport_code']));
            $query->whereHas('departureAirport', fn (Builder $q) => $q->where('code', $code));
        }

        // Arrival Airport Filter (by ID or Code)
        if (! empty($filters['arrival_airport_id'])) {
            $query->where('arrival_airport_id', $filters['arrival_airport_id']);
        } elseif (! empty($filters['arrival_airport_code'])) {
            $code = strtoupper(trim($filters['arrival_airport_code']));
            $query->whereHas('arrivalAirport', fn (Builder $q) => $q->where('code', $code));
        }

        // Departure & Arrival Cities
        if (! empty($filters['departure_city'])) {
            $city = trim($filters['departure_city']);
            $query->whereHas('departureAirport', fn (Builder $q) => $q->where('city', 'like', "%{$city}%"));
        }

        if (! empty($filters['arrival_city'])) {
            $city = trim($filters['arrival_city']);
            $query->whereHas('arrivalAirport', fn (Builder $q) => $q->where('city', 'like', "%{$city}%"));
        }

        // Departure Date Filter
        if (! empty($filters['departure_date'])) {
            $query->whereDate('departure_time', $filters['departure_date']);
        }

        // Airline Filter
        if (! empty($filters['airline_id'])) {
            if (is_array($filters['airline_id'])) {
                $query->whereIn('airline_id', $filters['airline_id']);
            } else {
                $query->where('airline_id', $filters['airline_id']);
            }
        }

        // Price Filter
        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Available Seats Filter (Passenger Capacity)
        if (! empty($filters['min_seats'])) {
            $query->where('available_seats', '>=', (int) $filters['min_seats']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'departure_time_asc';
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'departure_time_desc':
                $query->orderBy('departure_time', 'desc');
                break;
            case 'duration_asc':
                $query->orderBy('duration', 'asc');
                break;
            case 'duration_desc':
                $query->orderBy('duration', 'desc');
                break;
            case 'departure_time_asc':
            default:
                $query->orderBy('departure_time', 'asc');
                break;
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Flight
    {
        return Flight::with(['airline', 'departureAirport', 'arrivalAirport'])->findOrFail($id);
    }

    public function create(array $data): Flight
    {
        return Flight::create($data)->fresh(['airline', 'departureAirport', 'arrivalAirport']);
    }

    public function update(Flight $flight, array $data): Flight
    {
        $flight->update($data);
        return $flight->fresh(['airline', 'departureAirport', 'arrivalAirport']);
    }

    public function delete(Flight $flight): bool
    {
        return $flight->delete();
    }
}
