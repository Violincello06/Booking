<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class HotelService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->search([], $perPage);
    }

    public function search(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Hotel::query()
            ->with(['destination', 'rooms', 'reviews.user'])
            ->withCount(['rooms', 'reviews'])
            ->withMin('rooms', 'price_per_night')
            ->withMax('rooms', 'price_per_night')
            ->where('status', $filters['status'] ?? 'active');

        // Search Keyword
        if (! empty($filters['query'])) {
            $keyword = trim($filters['query']);
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%")
                  ->orWhereHas('destination', function (Builder $dest) use ($keyword) {
                      $dest->where('name', 'like', "%{$keyword}%")
                           ->orWhere('city', 'like', "%{$keyword}%")
                           ->orWhere('province', 'like', "%{$keyword}%");
                  });
            });
        }

        // Destination Filter
        if (! empty($filters['destination_id'])) {
            $query->where('destination_id', $filters['destination_id']);
        }

        // City Filter
        if (! empty($filters['city'])) {
            $city = trim($filters['city']);
            $query->where(function (Builder $q) use ($city) {
                $q->where('address', 'like', "%{$city}%")
                  ->orWhereHas('destination', function (Builder $dest) use ($city) {
                      $dest->where('city', 'like', "%{$city}%");
                  });
            });
        }

        // Star Rating Filter
        if (! empty($filters['star_rating'])) {
            $query->where('star_rating', $filters['star_rating']);
        }

        if (! empty($filters['min_rating'])) {
            $query->where('star_rating', '>=', $filters['min_rating']);
        }

        // Price Filter (via room price_per_night)
        if (! empty($filters['min_price']) || ! empty($filters['max_price'])) {
            $query->whereHas('rooms', function (Builder $roomQuery) use ($filters) {
                if (! empty($filters['min_price'])) {
                    $roomQuery->where('price_per_night', '>=', $filters['min_price']);
                }
                if (! empty($filters['max_price'])) {
                    $roomQuery->where('price_per_night', '<=', $filters['max_price']);
                }
            });
        }

        // Facility Filter
        if (! empty($filters['facility'])) {
            $facility = $filters['facility'];
            $query->whereHas('rooms', function (Builder $roomQuery) use ($facility) {
                $roomQuery->whereJsonContains('facilities', $facility);
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'latest';
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('rooms_min_price_per_night', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('rooms_max_price_per_night', 'desc');
                break;
            case 'rating_desc':
                $query->orderBy('star_rating', 'desc');
                break;
            case 'rating_asc':
                $query->orderBy('star_rating', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Hotel
    {
        return Hotel::with(['destination', 'rooms', 'reviews.user'])
            ->withCount(['rooms', 'reviews'])
            ->withMin('rooms', 'price_per_night')
            ->withMax('rooms', 'price_per_night')
            ->findOrFail($id);
    }

    public function create(array $data): Hotel
    {
        return Hotel::create($data);
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        $hotel->update($data);
        return $hotel->fresh(['destination', 'rooms']);
    }

    public function delete(Hotel $hotel): bool
    {
        return $hotel->delete();
    }
}
