<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComprehensiveSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected Hotel $hotel;
    protected Room $room;
    protected Flight $flight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create(['name' => 'Aditya Pratama', 'email' => 'aditya@example.com', 'role' => 'user']);
        $this->user2 = User::factory()->create(['name' => 'Bambang Sudirman', 'email' => 'bambang@example.com', 'role' => 'user']);

        $destination = Destination::factory()->create(['name' => 'Bali']);
        $this->hotel = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Violincello Royal Resort',
            'star_rating' => 5,
            'status' => 'active',
        ]);

        $this->room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type' => 'Presidential Suite',
            'price_per_night' => 2000000,
            'total_rooms' => 1,
            'available_rooms' => 1,
            'status' => 'available',
        ]);

        $airline = Airline::factory()->create(['name' => 'Garuda Indonesia', 'code' => 'GA']);
        $origin = Airport::factory()->create(['code' => 'CGK', 'city' => 'Jakarta']);
        $dest = Airport::factory()->create(['code' => 'DPS', 'city' => 'Bali']);

        $this->flight = Flight::create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $origin->id,
            'arrival_airport_id' => $dest->id,
            'flight_number' => 'GA-404',
            'departure_time' => now()->addDays(3)->setTime(10, 0),
            'arrival_time' => now()->addDays(3)->setTime(12, 0),
            'duration' => 120,
            'price' => 1500000,
            'total_seats' => 2,
            'available_seats' => 2,
            'status' => 'scheduled',
        ]);
    }

    public function test_concurrency_room_overbooking_prevented_when_inventory_reaches_zero(): void
    {
        // User 1 books the only 1 available room
        Sanctum::actingAs($this->user1);

        $res1 = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 2,
            'guests' => [
                ['full_name' => 'Aditya Pratama', 'gender' => 'male'],
                ['full_name' => 'Nadia Pratama', 'gender' => 'female'],
            ],
        ])->assertStatus(201);

        $this->assertEquals(0, $this->room->fresh()->available_rooms);
        $this->assertEquals('unavailable', $this->room->fresh()->status);

        // User 2 immediately attempts to book the same room
        Sanctum::actingAs($this->user2);

        $res2 = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Bambang Sudirman', 'gender' => 'male'],
            ],
        ]);

        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_concurrency_flight_seat_overbooking_prevented(): void
    {
        // User 1 books all 2 remaining seats
        Sanctum::actingAs($this->user1);

        $this->postJson('/api/v1/bookings/flight', [
            'flight_id' => $this->flight->id,
            'passenger_count' => 2,
            'passengers' => [
                ['full_name' => 'Aditya Pratama', 'identity_number' => '3201998877660001', 'gender' => 'male'],
                ['full_name' => 'Nadia Pratama', 'identity_number' => '3201998877660002', 'gender' => 'female'],
            ],
        ])->assertStatus(201);

        $this->assertEquals(0, $this->flight->fresh()->available_seats);

        // User 2 attempts to book 1 seat on fully booked flight
        Sanctum::actingAs($this->user2);

        $this->postJson('/api/v1/bookings/flight', [
            'flight_id' => $this->flight->id,
            'passenger_count' => 1,
            'passengers' => [
                ['full_name' => 'Bambang Sudirman', 'identity_number' => '3201998877660003', 'gender' => 'male'],
            ],
        ])->assertStatus(422);
    }

    public function test_expired_booking_cancellation_artisan_command_releases_inventory(): void
    {
        Sanctum::actingAs($this->user1);

        // Book room
        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Aditya Pratama', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');
        $booking = Booking::where('booking_code', $bookingCode)->first();

        // Simulate expired timestamp
        $booking->update(['expired_at' => now()->subHours(3)]);
        $this->assertEquals(0, $this->room->fresh()->available_rooms);

        // Run Artisan command to cancel expired bookings
        $this->artisan('bookings:cancel-expired')
            ->expectsOutputToContain('Berhasil membatalkan 1 pemesanan')
            ->assertExitCode(0);

        // Verify status and restored inventory
        $this->assertEquals('cancelled', $booking->fresh()->status);
        $this->assertEquals(1, $this->room->fresh()->available_rooms);
        $this->assertEquals('available', $this->room->fresh()->status);

        // Verify notification was sent
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user1->id,
            'type' => 'cancellation',
            'title' => 'Pemesanan Kedaluwarsa Dibatalkan',
        ]);
    }

    public function test_cannot_checkout_cancelled_booking(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Aditya Pratama', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        // Cancel
        $this->postJson("/api/v1/bookings/{$bookingCode}/cancel")->assertStatus(200);

        // Attempt checkout on cancelled booking
        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'bca_va',
        ]);

        $checkoutRes->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_cannot_simulate_payment_twice(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Aditya Pratama', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'bca_va',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        // First simulation -> success
        $this->postJson("/api/v1/payments/{$paymentCode}/simulate", ['action' => 'success'])
            ->assertStatus(200);

        // Second simulation -> rejected
        $secondSim = $this->postJson("/api/v1/payments/{$paymentCode}/simulate", ['action' => 'success']);
        $secondSim->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_depleted_voucher_quota_rejected(): void
    {
        Sanctum::actingAs($this->user1);

        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Aditya Pratama', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        // Voucher with quota completely used
        $voucher = Voucher::factory()->create([
            'code' => 'HABISKUOTA',
            'quota' => 5,
            'used_quota' => 5,
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(5),
            'minimum_transaction' => 100000,
        ]);

        $applyRes = $this->postJson('/api/v1/vouchers/apply', [
            'booking_code' => $bookingCode,
            'code' => 'HABISKUOTA',
        ]);

        $applyRes->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Kuota penukaran voucher promo ini telah habis.',
            ]);
    }

    /**
     * Complete Full End-to-End User Journey:
     * Register -> Login -> Search -> Voucher -> Reserve -> Apply Promo -> Checkout -> Pay -> Ticket -> Review -> Notif
     */
    public function test_full_end_to_end_user_journey(): void
    {
        // 1. User Registration
        $registerRes = $this->postJson('/api/v1/register', [
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '081298765432',
        ])->assertStatus(201);

        $token = $registerRes->json('data.token');
        $this->assertNotEmpty($token);

        // 2. Search Hotels
        $searchRes = $this->getJson('/api/v1/search/hotels?query=Violincello&star_rating=5')
            ->assertStatus(200);
        $this->assertNotEmpty($searchRes->json('data'));

        // 3. Browse Active Vouchers
        $voucher = Voucher::factory()->create([
            'code' => 'JOURNEY10',
            'name' => 'Diskon 10%',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_transaction' => 1000000,
            'maximum_discount' => 300000,
            'quota' => 100,
            'used_quota' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $vouchersRes = $this->getJson('/api/v1/vouchers')->assertStatus(200);
        $this->assertNotEmpty($vouchersRes->json('data'));

        // 4. Reserve Hotel Room (with Bearer Token)
        $bookRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/bookings/hotel', [
                'room_id' => $this->room->id,
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(4)->toDateString(), // 2 nights x 2.000.000 = 4.000.000
                'room_count' => 1,
                'guest_count' => 1,
                'guests' => [
                    ['full_name' => 'Dewi Lestari', 'gender' => 'female'],
                ],
            ])->assertStatus(201);

        $bookingCode = $bookRes->json('data.booking_code');
        $this->assertEquals(4000000, $bookRes->json('data.total_amount'));

        // 5. Apply Promo Voucher (10% of 4.000.000 = 400.000, capped at 300.000 -> 3.700.000)
        $voucherRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/vouchers/apply', [
                'booking_code' => $bookingCode,
                'code' => 'JOURNEY10',
            ])->assertStatus(200);

        $this->assertEquals(3700000, $voucherRes->json('data.final_amount'));
        $this->assertEquals(300000, $voucherRes->json('data.discount_amount'));

        // 6. Checkout Payment
        $checkoutRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/payments/checkout', [
                'booking_code' => $bookingCode,
                'payment_method' => 'bca_va',
            ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');
        $this->assertEquals(3700000, $checkoutRes->json('data.amount'));

        // 7. Simulate Payment Gateway Success
        $paySimRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/payments/{$paymentCode}/simulate", [
                'action' => 'success',
            ])->assertStatus(200);

        $this->assertEquals('paid', $paySimRes->json('data.status'));
        $this->assertEquals('confirmed', $paySimRes->json('data.booking.status'));

        // 8. Retrieve E-Ticket & Confirmation
        $confirmRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/bookings/{$bookingCode}/confirmation")
            ->assertStatus(200);

        $this->assertEquals('confirmed', $confirmRes->json('data.status'));
        $this->assertEquals('paid', $confirmRes->json('data.payment.status'));

        // 9. Submit Verified Review for the Stay
        $reviewRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/reviews', [
                'booking_code' => $bookingCode,
                'rating' => 5,
                'comment' => 'Pengalaman menginap yang luar biasa dan pemandangan kamar memukau!',
            ])->assertStatus(201);

        $this->assertEquals(5, $reviewRes->json('data.rating'));

        // 10. Check Notifications in Inbox
        $notifsRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/notifications')
            ->assertStatus(200);

        $this->assertGreaterThanOrEqual(2, count($notifsRes->json('data.notifications')));
    }
}
