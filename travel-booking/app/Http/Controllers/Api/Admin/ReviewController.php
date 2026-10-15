<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * List all reviews for moderation.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['status', 'hotel_id', 'rating']);

        $paginator = $this->reviewService->getAdminReviews($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua ulasan untuk moderasi admin',
            'data' => ReviewResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Update review status (publish, hide, or set pending).
     */
    public function updateStatus(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:published,pending,hidden'],
        ], [
            'status.in' => 'Status harus salah satu dari: published, pending, hidden.',
        ]);

        $updated = $this->reviewService->updateReviewStatus($review, $request->input('status'));

        return response()->json([
            'success' => true,
            'message' => "Status ulasan berhasil diubah menjadi {$updated->status}",
            'data' => new ReviewResource($updated),
        ], 200);
    }

    /**
     * Delete review.
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->reviewService->deleteReview($review);

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dihapus dari sistem',
        ], 200);
    }
}
