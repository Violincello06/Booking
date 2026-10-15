<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService
    ) {}

    /**
     * Get booking transaction report.
     */
    public function bookings(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = $request->only(['start_date', 'end_date', 'status', 'booking_type', 'search']);

        $report = $this->dashboardService->getBookingReports($filters, $perPage);
        $paginator = $report['paginator'];

        $formatted = collect($paginator->items())->map(function ($booking) {
            $entityName = null;
            if ($booking->booking_type === 'hotel' && $booking->hotelBooking) {
                $entityName = $booking->hotelBooking->room?->hotel?->name ?? 'Hotel';
            } elseif ($booking->booking_type === 'flight' && $booking->flightBooking) {
                $airline = $booking->flightBooking->flight?->airline;
                $entityName = $airline ? "{$airline->name} ({$airline->code})" : 'Penerbangan';
            }

            return [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'booking_type' => $booking->booking_type,
                'entity_name' => $entityName,
                'user' => [
                    'id' => $booking->user?->id,
                    'name' => $booking->user?->name,
                    'email' => $booking->user?->email,
                ],
                'total_amount' => (float) $booking->total_amount,
                'status' => $booking->status,
                'payment_status' => $booking->payment?->status ?? 'unpaid',
                'payment_method' => $booking->payment?->payment_method,
                'booking_date' => $booking->booking_date?->toIso8601String(),
                'created_at' => $booking->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan transaksi reservasi berhasil dimuat',
            'summary' => $report['summary'],
            'data' => $formatted,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get revenue and financial report.
     */
    public function revenue(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date']);
        $report = $this->dashboardService->getRevenueReports($filters);

        return response()->json([
            'success' => true,
            'message' => 'Laporan pendapatan & channel pembayaran berhasil dimuat',
            'data' => $report,
        ], 200);
    }
}
