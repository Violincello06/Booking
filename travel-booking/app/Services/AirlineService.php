<?php

namespace App\Services;

use App\Models\Airline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AirlineService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Airline::withCount('flights')->latest()->paginate($perPage);
    }

    public function find(int $id): Airline
    {
        return Airline::withCount('flights')->findOrFail($id);
    }

    public function create(array $data): Airline
    {
        return Airline::create($data);
    }

    public function update(Airline $airline, array $data): Airline
    {
        $airline->update($data);
        return $airline->fresh();
    }

    public function delete(Airline $airline): bool
    {
        return $airline->delete();
    }
}
