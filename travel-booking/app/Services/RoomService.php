<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomService
{
    public function paginate(int $perPage = 10, ?int $hotelId = null): LengthAwarePaginator
    {
        $query = Room::with('hotel.destination');

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function find(int $id): Room
    {
        return Room::with('hotel.destination')->findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);
        return $room->fresh('hotel');
    }

    public function delete(Room $room): bool
    {
        return $room->delete();
    }
}
