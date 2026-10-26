<?php

namespace App\Services;

use App\Models\Airline;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Get aggregate KPI metrics for dashboard overview.
     */
    public function getSummaryMetrics(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->month;
        $thisYear = Carbon::now()->year;

        // Revenue metrics
        $totalRevenue = (float) Payment::where('status', 'paid')->sum('amount');
        $todayRevenue = (float) Payment::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('amount');
        $thisMonthRevenue = (float) Payment::where('status', 'paid')
            ->whereMonth('paid_at', $thisMonth)
            ->whereYear('paid_at', $thisYear)
            ->sum('amount');

        // Bookings count & breakdown
        $bookingsQuery = Booking::query();
        $totalBookings = (clone $bookingsQuery)->count();
        $confirmedBookings = (clone $bookingsQuery)->where('status', 'confirmed')->count();
        $completedBookings = (clone $bookingsQuery)->where('status', 'completed')->count();
        $pendingBookings = (clone $bookingsQuery)->where('status', 'pending')->count();
        $cancelledBookings = (clone $bookingsQuery)->where('status', 'cancelled')->count();

        $hotelBookings = (clone $bookingsQuery)->where('booking_type', 'hotel')->count();
        $flightBookings = (clone $bookingsQuery)->where('booking_type', 'flight')->count();

        // User metrics
        $totalUsers = User::count();
        $regularUsers = User::where('role', 'user')->count();
        $staffUsers = User::where('role', 'staff')->count();
        $adminUsers = User::where('role', 'admin')->count();

        // Inventory metrics
        $totalHotels = Hotel::count();
        $activeHotels = Hotel::where('status', 'active')->count();
        $totalFlights = Flight::count();
        $scheduledFlights = Flight::where('status', 'scheduled')->count();
        $activeVouchers = Voucher::where('status', 'active')->count();

        return [
            'revenue' => [
                'total' => $totalRevenue,
                'today' => $todayRevenue,
                'this_month' => $thisMonthRevenue,
            ],
            'bookings' => [
                'total' => $totalBookings,
                'confirmed' => $confirmedBookings,
                'completed' => $completedBookings,
                'pending' => $pendingBookings,
                'cancelled' => $cancelledBookings,
                'by_type' => [
                    'hotel' => $hotelBookings,
                    'flight' => $flightBookings,
                ],
            ],
            'users' => [
                'total' => $totalUsers,
                'by_role' => [
                    'user' => $regularUsers,
                    'staff' => $staffUsers,
                    'admin' => $adminUsers,
                ],
            ],
            'inventory' => [
                'hotels' => [
                    'total' => $totalHotels,
                    'active' => $activeHotels,
                ],
                'flights' => [
                    'total' => $totalFlights,
                    'scheduled' => $scheduledFlights,
                ],
                'active_vouchers' => $activeVouchers,
            ],
        ];
    }

    /**
     * Get chart analytics (daily and monthly time-series).
     */
    public function getChartAnalytics(int $days = 14): array
    {
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today()->endOfDay();

        // Daily trend
        $dailyRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total_revenue, COUNT(*) as transaction_count')
            ->groupBy('date')
            ->pluck('total_revenue', 'date')
            ->toArray();

        $dailyBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailySeries = [];
        for ($i = 0; $i < $days; $i++) {
            $currDate = Carbon::today()->subDays($days - 1 - $i)->toDateString();
            $dailySeries[] = [
                'date' => $currDate,
                'revenue' => (float) ($dailyRevenue[$currDate] ?? 0),
                'bookings_count' => (int) ($dailyBookings[$currDate] ?? 0),
            ];
        }

        // Monthly trend for current year
        $currentYear = Carbon::now()->year;
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereYear('paid_at', $currentYear)
            ->selectRaw('MONTH(paid_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyBookings = Booking::whereYear('created_at', $currentYear)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $monthlySeries = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthName = Carbon::create($currentYear, $m, 1)->translatedFormat('M');
            $monthlySeries[] = [
                'month' => $m,
                'month_name' => $monthName,
                'revenue' => (float) ($monthlyRevenue[$m] ?? 0),
                'bookings_count' => (int) ($monthlyBookings[$m] ?? 0),
            ];
        }

        return [
            'daily_trend' => $dailySeries,
            'monthly_trend' => $monthlySeries,
        ];
    }

    /**
     * Get top-performing hotels, airlines, and vouchers.
     */
    public function getTopPerformers(int $limit = 5): array
    {
        // Top Hotels by bookings
        $topHotels = DB::table('hotel_bookings')
            ->join('rooms', 'hotel_bookings.room_id', '=', 'rooms.id')
            ->join('hotels', 'rooms.hotel_id', '=', 'hotels.id')
            ->join('bookings', 'hotel_bookings.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->select(
                'hotels.id',
                'hotels.name',
                'hotels.star_rating',
                DB::raw('COUNT(hotel_bookings.id) as total_bookings'),
                DB::raw('SUM(hotel_bookings.subtotal) as total_revenue')
            )
            ->groupBy('hotels.id', 'hotels.name', 'hotels.star_rating')
            ->orderByDesc('total_bookings')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'star_rating' => (int) $item->star_rating,
                'total_bookings' => (int) $item->total_bookings,
                'total_revenue' => (float) $item->total_revenue,
            ]);

        // Top Airlines by bookings
        $topAirlines = DB::table('flight_bookings')
            ->join('flights', 'flight_bookings.flight_id', '=', 'flights.id')
            ->join('airlines', 'flights.airline_id', '=', 'airlines.id')
            ->join('bookings', 'flight_bookings.booking_id', '=', 'bookings.id')
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->select(
                'airlines.id',
                'airlines.name',
                'airlines.code',
                DB::raw('COUNT(flight_bookings.id) as total_bookings'),
                DB::raw('SUM(flight_bookings.subtotal) as total_revenue')
            )
            ->groupBy('airlines.id', 'airlines.name', 'airlines.code')
            ->orderByDesc('total_bookings')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'total_bookings' => (int) $item->total_bookings,
                'total_revenue' => (float) $item->total_revenue,
            ]);

        // Top Vouchers by usage
        $topVouchers = DB::table('voucher_usages')
            ->join('vouchers', 'voucher_usages.voucher_id', '=', 'vouchers.id')
            ->select(
                'vouchers.id',
                'vouchers.code',
                'vouchers.name',
                'vouchers.discount_type',
                DB::raw('COUNT(voucher_usages.id) as usage_count'),
                DB::raw('SUM(voucher_usages.discount_amount) as total_discount')
            )
            ->groupBy('vouchers.id', 'vouchers.code', 'vouchers.name', 'vouchers.discount_type')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'discount_type' => $item->discount_type,
                'usage_count' => (int) $item->usage_count,
                'total_discount' => (float) $item->total_discount,
            ]);

        return [
            'top_hotels' => $topHotels,
            'top_airlines' => $topAirlines,
            'top_vouchers' => $topVouchers,
        ];
    }

    /**
     * Get filterable bookings report for export or table view.
     */
    public function getBookingReports(array $filters = [], int $perPage = 15): array
    {
        $query = Booking::with([
            'user:id,name,email',
            'hotelBooking.room.hotel:id,name',
            'flightBooking.flight.airline:id,name,code',
            'payment',
        ]);

        if (! empty($filters['start_date'])) {
            $query->whereDate('booking_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('booking_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['booking_type'])) {
            $query->where('booking_type', $filters['booking_type']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        // Summary of filtered query
        $summary = [
            'total_bookings' => (clone $query)->count(),
            'total_revenue' => (float) (clone $query)->whereIn('status', ['confirmed', 'completed'])->sum('total_amount'),
        ];

        $paginator = $query->latest('booking_date')->paginate($perPage);

        return [
            'summary' => $summary,
            'paginator' => $paginator,
        ];
    }

    /**
     * Get revenue breakdown report by payment channels.
     */
    public function getRevenueReports(array $filters = []): array
    {
        $query = Payment::where('status', 'paid');

        if (! empty($filters['start_date'])) {
            $query->whereDate('paid_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('paid_at', '<=', $filters['end_date']);
        }

        $totalRevenue = (float) (clone $query)->sum('amount');
        $totalTransactions = (int) (clone $query)->count();

        $byMethod = (clone $query)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'payment_method' => $item->payment_method,
                'count' => (int) $item->count,
                'total' => (float) $item->total,
                'percentage' => $totalRevenue > 0 ? round(($item->total / $totalRevenue) * 100, 2) : 0,
            ]);

        return [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
            ],
            'breakdown_by_method' => $byMethod,
        ];
    }
}
