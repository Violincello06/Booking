<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Submit a review for a completed/confirmed hotel reservation.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->createReview(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil disimpan dan dipublikasikan',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Get published reviews and ratings statistics for a hotel.
     */
    public function hotelReviews(Request $request, int $id): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $result = $this->reviewService->getHotelReviews($id, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar ulasan hotel berhasil dimuat',
            'data' => [
                'hotel' => $result['hotel'],
                'summary' => $result['summary'],
                'reviews' => ReviewResource::collection($result['reviews']->items()),
            ],
            'meta' => [
                'current_page' => $result['reviews']->currentPage(),
                'per_page' => $result['reviews']->perPage(),
                'total' => $result['reviews']->total(),
                'last_page' => $result['reviews']->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get list of reviews created by authenticated user.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $paginator = $this->reviewService->getUserReviews($request->user(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar ulasan saya berhasil dimuat',
            'data' => ReviewResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }
}
