<?php

namespace App\Services;

use App\Models\Airport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AirportService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Airport::withCount(['departureFlights', 'arrivalFlights'])->latest()->paginate($perPage);
    }

    public function find(int $id): Airport
    {
        return Airport::withCount(['departureFlights', 'arrivalFlights'])->findOrFail($id);
    }

    public function create(array $data): Airport
    {
        return Airport::create($data);
    }

    public function update(Airport $airport, array $data): Airport
    {
        $airport->update($data);
        return $airport->fresh();
    }

    public function delete(Airport $airport): bool
    {
        return $airport->delete();
    }
}
