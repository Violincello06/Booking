<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookFlightRequest;
use App\Http\Requests\BookHotelRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Get list of bookings for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);
        $filters = $request->only(['status', 'booking_type']);
        $paginator = $this->bookingService->getUserBookings($request->user(), $filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat pemesanan berhasil diambil',
            'data' => BookingResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Create a hotel reservation.
     */
    public function bookHotel(BookHotelRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->createHotelBooking($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Pemesanan hotel berhasil dibuat. Silakan lanjutkan pembayaran sebelum batas waktu.',
                'data' => new BookingResource($booking),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a flight reservation.
     */
    public function bookFlight(BookFlightRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->createFlightBooking($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Pemesanan tiket pesawat berhasil dibuat. Silakan lanjutkan pembayaran sebelum batas waktu.',
                'data' => new BookingResource($booking),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get booking details by booking code or ID.
     */
    public function show(Request $request, string $code): JsonResponse
    {
        try {
            $booking = $this->bookingService->findUserBooking($request->user(), $code);

            return response()->json([
                'success' => true,
                'message' => 'Detail pemesanan berhasil diambil',
                'data' => new BookingResource($booking),
            ], 200);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Pemesanan tidak ditemukan atau anda tidak memiliki akses.',
            ], 404);
        }
    }

    /**
     * Cancel a pending booking.
     */
    public function cancel(Request $request, string $code): JsonResponse
    {
        try {
            $booking = $this->bookingService->cancelBooking($request->user(), $code);

            return response()->json([
                'success' => true,
                'message' => 'Pemesanan berhasil dibatalkan dan inventori telah dipulihkan.',
                'data' => new BookingResource($booking),
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Pemesanan tidak ditemukan atau anda tidak memiliki akses.',
            ], 404);
        }
    }
}
