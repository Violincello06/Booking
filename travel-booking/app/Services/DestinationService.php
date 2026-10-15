<?php

namespace App\Services;

use App\Models\Destination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DestinationService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Destination::withCount('hotels')->latest()->paginate($perPage);
    }

    public function find(int $id): Destination
    {
        return Destination::with(['hotels.rooms'])->findOrFail($id);
    }

    public function create(array $data): Destination
    {
        return Destination::create($data);
    }

    public function update(Destination $destination, array $data): Destination
    {
        $destination->update($data);
        return $destination->fresh();
    }

    public function delete(Destination $destination): bool
    {
        return $destination->delete();
    }
}
