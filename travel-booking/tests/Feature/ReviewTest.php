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
use App\Models\Review;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Hotel $hotel;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'user']);
        $this->admin = User::factory()->create(['name' => 'Admin Utama', 'role' => 'admin']);

        $destination = Destination::factory()->create();
        $this->hotel = Hotel::factory()->create([
            'destination_id' => $destination->id,
            'name' => 'Grand Melia Resort',
            'star_rating' => 5,
        ]);

        $this->room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'price_per_night' => 1500000,
            'available_rooms' => 10,
            'status' => 'available',
        ]);
    }

    /**
     * Helper to create a booking with status.
     */
    protected function createHotelBooking(User $user, string $status = 'confirmed'): Booking
    {
        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'BK-HTL-' . strtoupper(fake()->bothify('????####')),
            'booking_type' => 'hotel',
            'booking_date' => now(),
            'status' => $status,
            'total_amount' => 1500000,
            'expired_at' => now()->addDay(),
        ]);

        HotelBooking::create([
            'booking_id' => $booking->id,
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guest_count' => 1,
            'room_count' => 1,
            'price' => 1500000,
            'subtotal' => 1500000,
        ]);

        return $booking;
    }

    /**
     * Helper to create a flight booking.
     */
    protected function createFlightBooking(User $user, string $status = 'confirmed'): Booking
    {
        $airline = Airline::factory()->create();
        $departure = Airport::factory()->create();
        $arrival = Airport::factory()->create();

        $flight = Flight::create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $departure->id,
            'arrival_airport_id' => $arrival->id,
            'flight_number' => 'GA-123',
            'departure_time' => now()->addDays(2),
            'arrival_time' => now()->addDays(2)->addHours(2),
            'duration' => 120,
            'price' => 1200000,
            'total_seats' => 100,
            'available_seats' => 100,
            'status' => 'scheduled',
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'booking_code' => 'BK-FLT-' . strtoupper(fake()->bothify('????####')),
            'booking_type' => 'flight',
            'booking_date' => now(),
            'status' => $status,
            'total_amount' => 1200000,
            'expired_at' => now()->addDay(),
        ]);

        FlightBooking::create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
            'seat_class' => 'economy',
            'passenger_count' => 1,
            'price' => 1200000,
            'subtotal' => 1200000,
        ]);

        return $booking;
    }

    public function test_user_can_submit_review_for_confirmed_hotel_booking(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'confirmed');

        $response = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 5,
            'comment' => 'Pelayanan sangat luar biasa dan pemandangan kamar indah sekali!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ulasan berhasil disimpan dan dipublikasikan',
                'data' => [
                    'hotel_id' => $this->hotel->id,
                    'rating' => 5,
                    'comment' => 'Pelayanan sangat luar biasa dan pemandangan kamar indah sekali!',
                    'status' => 'published',
                    'booking_code' => $booking->booking_code,
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $booking->id,
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'rating' => 5,
            'status' => 'published',
        ]);
    }

    public function test_user_can_submit_review_for_completed_hotel_booking(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'completed');

        $response = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 4,
            'comment' => 'Kamar bersih dan sarapan lezat.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'rating' => 4,
                ],
            ]);
    }

    public function test_user_cannot_submit_review_for_pending_booking(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'pending');

        $response = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 5,
            'comment' => 'Mencoba memberi ulasan lebih awal.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_code']);
    }

    public function test_user_cannot_submit_review_for_cancelled_booking(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'cancelled');

        $response = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 1,
            'comment' => 'Saya membatalkan pemesanan.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_code']);
    }

    public function test_user_cannot_submit_review_for_flight_booking(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createFlightBooking($this->user, 'confirmed');

        $response = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 4,
            'comment' => 'Penerbangan tepat waktu.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_code']);
    }

    public function test_user_cannot_submit_review_for_another_users_booking(): void
    {
        $otherUser = User::factory()->create(['name' => 'Siti Rahma']);
        $booking = $this->createHotelBooking($otherUser, 'confirmed');

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 5,
            'comment' => 'Mencoba mengulas tiket orang lain.',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_submit_duplicate_review_for_same_booking(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'confirmed');

        // First review
        $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 5,
            'comment' => 'Ulasan pertama.',
        ])->assertStatus(201);

        // Second review attempt
        $secondResponse = $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 4,
            'comment' => 'Ulasan kedua yang harus ditolak.',
        ]);

        $secondResponse->assertStatus(422)
            ->assertJsonValidationErrors(['booking_code']);
    }

    public function test_validation_requires_valid_rating_between_1_and_5(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'confirmed');

        // Rating > 5
        $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 6,
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);

        // Rating < 1
        $this->postJson('/api/v1/reviews', [
            'booking_code' => $booking->booking_code,
            'rating' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    public function test_public_can_view_hotel_reviews_and_rating_summary(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $booking1 = $this->createHotelBooking($user1, 'confirmed');
        $booking2 = $this->createHotelBooking($user2, 'confirmed');

        Review::factory()->create([
            'user_id' => $user1->id,
            'hotel_id' => $this->hotel->id,
            'booking_id' => $booking1->id,
            'rating' => 5,
            'comment' => 'Luar biasa!',
            'status' => 'published',
        ]);

        Review::factory()->create([
            'user_id' => $user2->id,
            'hotel_id' => $this->hotel->id,
            'booking_id' => $booking2->id,
            'rating' => 3,
            'comment' => 'Cukup bagus.',
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/hotels/{$this->hotel->id}/reviews");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'hotel' => [
                        'id' => $this->hotel->id,
                        'name' => $this->hotel->name,
                    ],
                    'summary' => [
                        'average_rating' => 4.0,
                        'total_reviews' => 2,
                        'breakdown' => [
                            '5_star' => 1,
                            '4_star' => 0,
                            '3_star' => 1,
                            '2_star' => 0,
                            '1_star' => 0,
                        ],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.reviews'));
    }

    public function test_user_can_view_their_own_reviews(): void
    {
        Sanctum::actingAs($this->user);
        $booking = $this->createHotelBooking($this->user, 'confirmed');

        Review::factory()->create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Pengalaman menginap saya.',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/reviews/my');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Pengalaman menginap saya.', $response->json('data.0.comment'));
    }

    public function test_admin_can_view_and_moderate_review_status(): void
    {
        $booking = $this->createHotelBooking($this->user, 'confirmed');

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'booking_id' => $booking->id,
            'rating' => 1,
            'comment' => 'Ulasan mengandung kata kasar.',
            'status' => 'published',
        ]);

        Sanctum::actingAs($this->admin);

        // List reviews
        $listRes = $this->getJson('/api/v1/admin/reviews');
        $listRes->assertStatus(200)
            ->assertJson(['success' => true]);

        // Hide review
        $patchRes = $this->patchJson("/api/v1/admin/reviews/{$review->id}/status", [
            'status' => 'hidden',
        ]);

        $patchRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $review->id,
                    'status' => 'hidden',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => 'hidden',
        ]);
    }

    public function test_admin_can_delete_review(): void
    {
        $booking = $this->createHotelBooking($this->user, 'confirmed');

        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'booking_id' => $booking->id,
            'rating' => 2,
            'status' => 'published',
        ]);

        Sanctum::actingAs($this->admin);

        $deleteRes = $this->deleteJson("/api/v1/admin/reviews/{$review->id}");

        $deleteRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ulasan berhasil dihapus dari sistem',
            ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }
}
