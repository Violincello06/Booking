<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService
    ) {}

    /**
     * Get aggregate KPI metrics for dashboard overview.
     */
    public function index(Request $request): JsonResponse
    {
        $metrics = $this->dashboardService->getSummaryMetrics();

        return response()->json([
            'success' => true,
            'message' => 'Akses admin berhasil diverifikasi',
            'data' => array_merge([
                'user' => $request->user()->name,
                'role' => $request->user()->role,
                'admin_user' => [
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                ],
            ], $metrics),
        ], 200);
    }

    /**
     * Get chart analytics for trends.
     */
    public function charts(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 14);
        $days = max(7, min(90, $days));

        $charts = $this->dashboardService->getChartAnalytics($days);

        return response()->json([
            'success' => true,
            'message' => 'Data grafik tren analitik berhasil dimuat',
            'data' => $charts,
        ], 200);
    }

    /**
     * Get top-performing hotels, airlines, and vouchers.
     */
    public function topPerformers(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 5);
        $limit = max(1, min(20, $limit));

        $top = $this->dashboardService->getTopPerformers($limit);

        return response()->json([
            'success' => true,
            'message' => 'Data performa teratas berhasil dimuat',
            'data' => $top,
        ], 200);
    }
}
