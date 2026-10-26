<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Hotel $hotel;
    protected Room $room;
    protected Flight $flight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Super Administrator', 'role' => 'admin']);
        $this->user = User::factory()->create(['name' => 'Budi Pelanggan', 'role' => 'user']);

        $destination = Destination::factory()->create();
        $this->hotel = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Ayana Resort & Spa',
            'star_rating' => 5,
        ]);

        $this->room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'price_per_night' => 2000000,
            'available_rooms' => 10,
            'status' => 'available',
        ]);

        $airline = Airline::factory()->create(['name' => 'Garuda Indonesia', 'code' => 'GA']);
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();

        $this->flight = Flight::create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'flight_number' => 'GA-100',
            'departure_time' => now()->addDays(2),
            'arrival_time' => now()->addDays(2)->addHours(2),
            'duration' => 120,
            'price' => 1500000,
            'total_seats' => 100,
            'available_seats' => 100,
            'status' => 'scheduled',
        ]);
    }

    public function test_unauthenticated_cannot_access_admin_dashboard(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/admin/dashboard/charts')->assertStatus(401);
        $this->getJson('/api/v1/admin/reports/bookings')->assertStatus(401);
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->getJson('/api/v1/admin/dashboard/charts')->assertStatus(403);
        $this->getJson('/api/v1/admin/reports/bookings')->assertStatus(403);
    }

    public function test_admin_can_view_summary_kpi_metrics(): void
    {
        // Create confirmed booking with payment
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'booking_code' => 'BK-HTL-TEST01',
            'booking_type' => 'hotel',
            'booking_date' => now(),
            'total_amount' => 4000000,
            'status' => 'confirmed',
            'expired_at' => now()->addDay(),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAY-TEST01',
            'payment_method' => 'bca_va',
            'amount' => 4000000,
            'status' => 'paid',
            'paid_at' => now(),
            'expired_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'revenue' => [
                        'total' => 4000000,
                    ],
                    'bookings' => [
                        'total' => 1,
                        'confirmed' => 1,
                        'by_type' => [
                            'hotel' => 1,
                            'flight' => 0,
                        ],
                    ],
                    'users' => [
                        'total' => 2,
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_chart_analytics(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/dashboard/charts?days=7');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'daily_trend',
                    'monthly_trend',
                ],
            ]);

        $this->assertCount(7, $response->json('data.daily_trend'));
        $this->assertCount(12, $response->json('data.monthly_trend'));
    }

    public function test_admin_can_view_top_performers(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'booking_code' => 'BK-HTL-TOP01',
            'booking_type' => 'hotel',
            'booking_date' => now(),
            'total_amount' => 6000000,
            'status' => 'confirmed',
            'expired_at' => now()->addDay(),
        ]);

        HotelBooking::create([
            'booking_id' => $booking->id,
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest_count' => 2,
            'room_count' => 1,
            'price' => 2000000,
            'subtotal' => 6000000,
        ]);

        $voucher = Voucher::factory()->create([
            'code' => 'TOPPROMO',
            'name' => 'Promo Top',
        ]);

        VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'user_id' => $this->user->id,
            'booking_id' => $booking->id,
            'discount_amount' => 500000,
            'used_at' => now(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/dashboard/top-performers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'top_hotels' => [
                        [
                            'id' => $this->hotel->id,
                            'name' => 'Ayana Resort & Spa',
                            'total_bookings' => 1,
                            'total_revenue' => 6000000,
                        ],
                    ],
                    'top_vouchers' => [
                        [
                            'code' => 'TOPPROMO',
                            'usage_count' => 1,
                            'total_discount' => 500000,
                        ],
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_booking_reports_with_filters(): void
    {
        $b1 = Booking::create([
            'user_id' => $this->user->id,
            'booking_code' => 'BK-HTL-REP01',
            'booking_type' => 'hotel',
            'booking_date' => now()->toDateString(),
            'total_amount' => 2000000,
            'status' => 'confirmed',
            'expired_at' => now()->addDay(),
        ]);

        $b2 = Booking::create([
            'user_id' => $this->user->id,
            'booking_code' => 'BK-FLT-REP02',
            'booking_type' => 'flight',
            'booking_date' => now()->toDateString(),
            'total_amount' => 1500000,
            'status' => 'pending',
            'expired_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($this->admin);

        // Filter by booking_type = hotel
        $resHotel = $this->getJson('/api/v1/admin/reports/bookings?booking_type=hotel');
        $resHotel->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total_bookings' => 1,
                    'total_revenue' => 2000000,
                ],
            ]);
        $this->assertEquals('BK-HTL-REP01', $resHotel->json('data.0.booking_code'));

        // Filter by status = pending
        $resPending = $this->getJson('/api/v1/admin/reports/bookings?status=pending');
        $resPending->assertStatus(200);
        $this->assertEquals('BK-FLT-REP02', $resPending->json('data.0.booking_code'));
    }

    public function test_admin_can_view_revenue_reports_breakdown(): void
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'booking_code' => 'BK-HTL-REV01',
            'booking_type' => 'hotel',
            'booking_date' => now(),
            'total_amount' => 5000000,
            'status' => 'confirmed',
            'expired_at' => now()->addDay(),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAY-REV01',
            'payment_method' => 'bca_va',
            'amount' => 3000000,
            'status' => 'paid',
            'paid_at' => now(),
            'expired_at' => now()->addDay(),
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'payment_code' => 'PAY-REV02',
            'payment_method' => 'gopay',
            'amount' => 2000000,
            'status' => 'paid',
            'paid_at' => now(),
            'expired_at' => now()->addDay(),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/reports/revenue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_revenue' => 5000000,
                        'total_transactions' => 2,
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.breakdown_by_method'));
    }
}
