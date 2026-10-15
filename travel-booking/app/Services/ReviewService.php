<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Submit a new review for a hotel booking.
     *
     * @throws ValidationException
     */
    public function createReview(User $user, array $data): Review
    {
        $booking = Booking::with(['hotelBooking.room.hotel'])
            ->where('booking_code', $data['booking_code'])
            ->first();

        if (! $booking) {
            throw ValidationException::withMessages([
                'booking_code' => ['Kode booking tidak ditemukan dalam sistem.'],
            ]);
        }

        // 1. Verify ownership
        if ($booking->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengulas pemesanan ini.');
        }

        // 2. Verify booking type
        if ($booking->booking_type !== 'hotel' || ! $booking->hotelBooking) {
            throw ValidationException::withMessages([
                'booking_code' => ['Ulasan hanya dapat diberikan untuk reservasi hotel.'],
            ]);
        }

        // 3. Verify booking status (only confirmed or completed bookings can be reviewed)
        if (! in_array($booking->status, ['confirmed', 'completed'], true)) {
            throw ValidationException::withMessages([
                'booking_code' => ["Ulasan hanya dapat diajukan untuk pemesanan yang telah terkonfirmasi atau selesai (status saat ini: {$booking->status})."],
            ]);
        }

        // 4. Verify no duplicate review
        if ($booking->review()->exists()) {
            throw ValidationException::withMessages([
                'booking_code' => ['Pemesanan ini sudah pernah Anda beri ulasan.'],
            ]);
        }

        // 5. Get hotel ID
        $hotelId = $booking->hotelBooking->room?->hotel_id;
        if (! $hotelId) {
            throw ValidationException::withMessages([
                'booking_code' => ['Informasi hotel terkait reservasi ini tidak valid.'],
            ]);
        }

        return DB::transaction(function () use ($user, $booking, $hotelId, $data) {
            $review = Review::create([
                'user_id' => $user->id,
                'hotel_id' => $hotelId,
                'booking_id' => $booking->id,
                'rating' => (int) $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => 'published',
            ]);

            return $review->load(['user', 'hotel', 'booking']);
        });
    }

    /**
     * Get published reviews and statistics for a specific hotel.
     */
    public function getHotelReviews(int $hotelId, int $perPage = 10): array
    {
        $hotel = Hotel::findOrFail($hotelId);

        $baseQuery = Review::where('hotel_id', $hotelId)->where('status', 'published');

        $totalReviews = (clone $baseQuery)->count();
        $averageRating = $totalReviews > 0
            ? (float) round((clone $baseQuery)->avg('rating'), 1)
            : 0.0;

        $breakdownRaw = (clone $baseQuery)
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $breakdown = [
            '5_star' => (int) ($breakdownRaw[5] ?? 0),
            '4_star' => (int) ($breakdownRaw[4] ?? 0),
            '3_star' => (int) ($breakdownRaw[3] ?? 0),
            '2_star' => (int) ($breakdownRaw[2] ?? 0),
            '1_star' => (int) ($breakdownRaw[1] ?? 0),
        ];

        $reviews = (clone $baseQuery)
            ->with(['user:id,name', 'hotel:id,name,star_rating', 'booking:id,booking_code'])
            ->latest()
            ->paginate($perPage);

        return [
            'hotel' => [
                'id' => $hotel->id,
                'name' => $hotel->name,
                'star_rating' => $hotel->star_rating,
            ],
            'summary' => [
                'average_rating' => $averageRating,
                'total_reviews' => $totalReviews,
                'breakdown' => $breakdown,
            ],
            'reviews' => $reviews,
        ];
    }

    /**
     * Get authenticated user's submitted reviews.
     */
    public function getUserReviews(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Review::where('user_id', $user->id)
            ->with(['hotel:id,name,star_rating', 'booking:id,booking_code'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * List reviews for admin with optional filters.
     */
    public function getAdminReviews(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Review::with(['user:id,name,email', 'hotel:id,name,star_rating', 'booking:id,booking_code']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Update review status (admin moderation).
     */
    public function updateReviewStatus(Review $review, string $status): Review
    {
        if (! in_array($status, ['published', 'pending', 'hidden'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Status ulasan harus salah satu dari: published, pending, hidden.'],
            ]);
        }

        $review->update(['status' => $status]);

        return $review->fresh(['user', 'hotel', 'booking']);
    }

    /**
     * Delete review (admin).
     */
    public function deleteReview(Review $review): bool
    {
        return $review->delete();
    }
}
