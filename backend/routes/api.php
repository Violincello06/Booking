<?php

use App\Http\Controllers\Api\AirlineController;
use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\Admin\AirlineController as AdminAirlineController;
use App\Http\Controllers\Api\Admin\AirportController as AdminAirportController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\DestinationController as AdminDestinationController;
use App\Http\Controllers\Api\Admin\FlightController as AdminFlightController;
use App\Http\Controllers\Api\Admin\HotelController as AdminHotelController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\VoucherController as AdminVoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Travel Booking & Reservation System
|--------------------------------------------------------------------------
|
| Version 1.0 (v1)
|
*/

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------------------
    // Public Endpoints
    // ---------------------------------------------------------------------
    Route::get('/health', HealthController::class)->name('api.v1.health');

    // Public Catalog & Details
    Route::get('/destinations', [DestinationController::class, 'index'])->name('api.v1.destinations.index');
    Route::get('/destinations/{id}', [DestinationController::class, 'show'])->name('api.v1.destinations.show');

    Route::get('/hotels', [HotelController::class, 'index'])->name('api.v1.hotels.index');
    Route::get('/hotels/{id}', [HotelController::class, 'show'])->name('api.v1.hotels.show');
    Route::get('/hotels/{id}/reviews', [ReviewController::class, 'hotelReviews'])->name('api.v1.hotels.reviews');

    Route::get('/flights', [FlightController::class, 'index'])->name('api.v1.flights.index');
    Route::get('/flights/{id}', [FlightController::class, 'show'])->name('api.v1.flights.show');

    Route::get('/airlines', [AirlineController::class, 'index'])->name('api.v1.airlines.index');
    Route::get('/airlines/{id}', [AirlineController::class, 'show'])->name('api.v1.airlines.show');

    Route::get('/airports', [AirportController::class, 'index'])->name('api.v1.airports.index');
    Route::get('/airports/{id}', [AirportController::class, 'show'])->name('api.v1.airports.show');

    // Advanced Search & Filter Endpoints
    Route::get('/search/hotels', [SearchController::class, 'hotels'])->name('api.v1.search.hotels');
    Route::get('/search/flights', [SearchController::class, 'flights'])->name('api.v1.search.flights');

    // Public Vouchers Catalog
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('api.v1.vouchers.index');

    // Authentication (Rate limited to prevent brute-force attacks)
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    // ---------------------------------------------------------------------
    // Authenticated Endpoints (Sanctum Bearer Token)
    // ---------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        // User Profile & Logout
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // -----------------------------------------------------------------
        // Booking Engine (Hotel & Flight Reservations)
        // -----------------------------------------------------------------
        Route::get('/bookings', [BookingController::class, 'index'])->name('api.v1.bookings.index');
        Route::post('/bookings/hotel', [BookingController::class, 'bookHotel'])->name('api.v1.bookings.hotel');
        Route::post('/bookings/flight', [BookingController::class, 'bookFlight'])->name('api.v1.bookings.flight');
        Route::get('/bookings/{code}', [BookingController::class, 'show'])->name('api.v1.bookings.show');
        Route::post('/bookings/{code}/cancel', [BookingController::class, 'cancel'])->name('api.v1.bookings.cancel');

        // -----------------------------------------------------------------
        // Payment Gateway Mock & Booking Confirmation
        // -----------------------------------------------------------------
        Route::post('/payments/checkout', [PaymentController::class, 'checkout'])->name('api.v1.payments.checkout');
        Route::post('/payments/{code}/simulate', [PaymentController::class, 'simulate'])->name('api.v1.payments.simulate');
        Route::get('/payments/{code}/status', [PaymentController::class, 'status'])->name('api.v1.payments.status');
        Route::get('/bookings/{code}/confirmation', [PaymentController::class, 'confirmation'])->name('api.v1.bookings.confirmation');

        // -----------------------------------------------------------------
        // -----------------------------------------------------------------
        // Voucher Promo & Discounts
        // -----------------------------------------------------------------
        Route::post('/vouchers/apply', [VoucherController::class, 'apply'])->name('api.v1.vouchers.apply');
        Route::get('/vouchers/usages', [VoucherController::class, 'usages'])->name('api.v1.vouchers.usages');

        // -----------------------------------------------------------------
        // Review & Rating System
        // -----------------------------------------------------------------
        Route::post('/reviews', [ReviewController::class, 'store'])->name('api.v1.reviews.store');
        Route::get('/reviews/my', [ReviewController::class, 'myReviews'])->name('api.v1.reviews.my');

        // -----------------------------------------------------------------
        // Notification System
        // -----------------------------------------------------------------
        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.v1.notifications.unread-count');
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.v1.notifications.mark-as-read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.v1.notifications.mark-all-read');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('api.v1.notifications.destroy');

        // -----------------------------------------------------------------
        // Role Protected Endpoints (Staff & Admin)
        // -----------------------------------------------------------------
        Route::middleware('role:staff,admin')->prefix('staff')->group(function () {
            Route::get('/operations', function (Request $request) {
                return response()->json([
                    'success' => true,
                    'message' => 'Akses staf/admin berhasil diverifikasi',
                    'data' => [
                        'user' => $request->user()->name,
                        'role' => $request->user()->role,
                    ],
                ]);
            })->name('api.v1.staff.operations');
        });

        // -----------------------------------------------------------------
        // Admin Master CRUD Endpoints
        // -----------------------------------------------------------------
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Dashboard KPIs & Analytics
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('api.v1.admin.dashboard');
            Route::get('/dashboard/charts', [AdminDashboardController::class, 'charts'])->name('api.v1.admin.dashboard.charts');
            Route::get('/dashboard/top-performers', [AdminDashboardController::class, 'topPerformers'])->name('api.v1.admin.dashboard.top-performers');

            // Reports & Analytics
            Route::get('/reports/bookings', [AdminReportController::class, 'bookings'])->name('api.v1.admin.reports.bookings');
            Route::get('/reports/revenue', [AdminReportController::class, 'revenue'])->name('api.v1.admin.reports.revenue');

            // Master CRUD resources
            Route::apiResource('destinations', AdminDestinationController::class);
            Route::apiResource('hotels', AdminHotelController::class);
            Route::apiResource('rooms', AdminRoomController::class);
            Route::apiResource('airlines', AdminAirlineController::class);
            Route::apiResource('airports', AdminAirportController::class);
            Route::apiResource('flights', AdminFlightController::class);
            Route::apiResource('vouchers', AdminVoucherController::class);
            Route::apiResource('users', AdminUserController::class);

            // Review moderation
            Route::get('/reviews', [AdminReviewController::class, 'index'])->name('api.v1.admin.reviews.index');
            Route::patch('/reviews/{review}/status', [AdminReviewController::class, 'updateStatus'])->name('api.v1.admin.reviews.update-status');
            Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('api.v1.admin.reviews.destroy');

            // Notification broadcast & system list
            Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('api.v1.admin.notifications.index');
            Route::post('/notifications/broadcast', [AdminNotificationController::class, 'broadcast'])->name('api.v1.admin.notifications.broadcast');
        });
    });
});

// ---------------------------------------------------------------------
// OTA Management Portal Routes (Supporting public/index.html & db.json)
// ---------------------------------------------------------------------
use App\Http\Controllers\Api\OtaDataController;

Route::post('/auth/login', [OtaDataController::class, 'login']);
Route::post('/upload', [OtaDataController::class, 'upload']);

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [OtaDataController::class, 'dashboard']);
    Route::post('/upload', [OtaDataController::class, 'upload']);

    // Destinations
    Route::get('/destinations', [OtaDataController::class, 'getDestinations']);
    Route::post('/destinations', [OtaDataController::class, 'storeDestination']);
    Route::put('/destinations/{id}', [OtaDataController::class, 'updateDestination']);
    Route::delete('/destinations/{id}', [OtaDataController::class, 'deleteDestination']);

    // Hotels
    Route::get('/hotels', [OtaDataController::class, 'getHotels']);
    Route::post('/hotels', [OtaDataController::class, 'storeHotel']);
    Route::put('/hotels/{id}', [OtaDataController::class, 'updateHotel']);
    Route::delete('/hotels/{id}', [OtaDataController::class, 'deleteHotel']);

    // Flights
    Route::get('/flights', [OtaDataController::class, 'getFlights']);
    Route::post('/flights', [OtaDataController::class, 'storeFlight']);
    Route::put('/flights/{id}', [OtaDataController::class, 'updateFlight']);
    Route::delete('/flights/{id}', [OtaDataController::class, 'deleteFlight']);

    // Blogs
    Route::get('/blogs', [OtaDataController::class, 'getBlogs']);
    Route::post('/blogs', [OtaDataController::class, 'storeBlog']);
    Route::put('/blogs/{id}', [OtaDataController::class, 'updateBlog']);
    Route::delete('/blogs/{id}', [OtaDataController::class, 'deleteBlog']);
});

