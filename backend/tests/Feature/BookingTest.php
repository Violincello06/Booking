<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Budi Santoso']);
        $this->otherUser = User::factory()->create(['name' => 'Siti Aminah']);
    }

    public function test_unauthenticated_user_cannot_book(): void
    {
        $this->postJson('/api/v1/bookings/hotel', [])
            ->assertStatus(401);

        $this->postJson('/api/v1/bookings/flight', [])
            ->assertStatus(401);
    }

    public function test_user_can_book_hotel_room_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $destination = Destination::factory()->create();
        $hotel = Hotel::factory()->create(['destination_id' => $destination->id]);
        $room = Room::factory()->create([
            'hotel_id' => $hotel->id,
            'price_per_night' => 1000000,
            'total_rooms' => 10,
            'available_rooms' => 10,
            'status' => 'available',
        ]);

        $checkIn = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(5)->toDateString(); // 3 nights

        $response = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'room_count' => 2,
            'guest_count' => 2,
            'guests' => [
                [
                    'full_name' => 'Budi Santoso',
                    'identity_number' => '3201123456780001',
                    'gender' => 'male',
                    'nationality' => 'Indonesia',
                ],
                [
                    'full_name' => 'Dewi Lestari',
                    'identity_number' => '3201123456780002',
                    'gender' => 'female',
                    'nationality' => 'Indonesia',
                ],
            ],
        ])->assertStatus(201)
          ->assertJsonPath('data.booking_type', 'hotel')
          ->assertJsonPath('data.status', 'pending');

        $bookingCode = $response->json('data.booking_code');
        $this->assertStringStartsWith('BK-HTL-', $bookingCode);

        // 3 nights * 1.000.000 * 2 rooms = 6.000.000
        $this->assertEquals(6000000, $response->json('data.total_amount'));

        // Verify available rooms decremented from 10 to 8
        $this->assertEquals(8, $room->fresh()->available_rooms);

        // Verify passengers count
        $this->assertCount(2, $response->json('data.passengers'));
    }

    public function test_booking_hotel_fails_when_room_is_overbooked(): void
    {
        Sanctum::actingAs($this->user);

        $room = Room::factory()->create([
            'available_rooms' => 1,
            'status' => 'available',
        ]);

        $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(1)->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'room_count' => 2, // Requesting 2 rooms while only 1 is available
            'guest_count' => 2,
            'guests' => [
                [
                    'full_name' => 'Budi Santoso',
                    'gender' => 'male',
                ],
            ],
        ])->assertStatus(422)
          ->assertJsonPath('success', false);

        // Available rooms should remain 1
        $this->assertEquals(1, $room->fresh()->available_rooms);
    }

    public function test_user_can_book_flight_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $airline = Airline::factory()->create();
        $dept = Airport::factory()->create();
        $arr = Airport::factory()->create();

        $flight = Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $dept->id,
            'arrival_airport_id' => $arr->id,
            'price' => 1250000,
            'total_seats' => 100,
            'available_seats' => 50,
            'status' => 'scheduled',
        ]);

        $response = $this->postJson('/api/v1/bookings/flight', [
            'flight_id' => $flight->id,
            'passenger_count' => 2,
            'passengers' => [
                [
                    'full_name' => 'Budi Santoso',
                    'identity_number' => '3201123456780001',
                    'gender' => 'male',
                    'nationality' => 'Indonesia',
                ],
                [
                    'full_name' => 'Dewi Lestari',
                    'identity_number' => '3201123456780002',
                    'gender' => 'female',
                    'nationality' => 'Indonesia',
                ],
            ],
        ])->assertStatus(201)
          ->assertJsonPath('data.booking_type', 'flight')
          ->assertJsonPath('data.status', 'pending');

        $bookingCode = $response->json('data.booking_code');
        $this->assertStringStartsWith('BK-FLT-', $bookingCode);

        // 2 passengers * 1.250.000 = 2.500.000
        $this->assertEquals(2500000, $response->json('data.total_amount'));

        // Verify available seats decremented from 50 to 48
        $this->assertEquals(48, $flight->fresh()->available_seats);
    }

    public function test_booking_flight_fails_when_seats_are_insufficient(): void
    {
        Sanctum::actingAs($this->user);

        $flight = Flight::factory()->create([
            'available_seats' => 1,
            'status' => 'scheduled',
        ]);

        $this->postJson('/api/v1/bookings/flight', [
            'flight_id' => $flight->id,
            'passenger_count' => 3,
            'passengers' => [
                ['full_name' => 'P1', 'identity_number' => 'ID1', 'gender' => 'male'],
                ['full_name' => 'P2', 'identity_number' => 'ID2', 'gender' => 'female'],
                ['full_name' => 'P3', 'identity_number' => 'ID3', 'gender' => 'male'],
            ],
        ])->assertStatus(422)
          ->assertJsonPath('success', false);

        // Seats remain 1
        $this->assertEquals(1, $flight->fresh()->available_seats);
    }

    public function test_user_can_view_booking_history_and_detail(): void
    {
        Sanctum::actingAs($this->user);

        $room = Room::factory()->create(['available_rooms' => 5]);

        $bookRes = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $code = $bookRes->json('data.booking_code');

        // View list
        $this->getJson('/api/v1/bookings')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', $code);

        // View detail
        $this->getJson("/api/v1/bookings/{$code}")
            ->assertStatus(200)
            ->assertJsonPath('data.booking_code', $code)
            ->assertJsonPath('data.passengers.0.full_name', 'Budi Santoso');
    }

    public function test_user_cannot_view_or_cancel_other_users_booking(): void
    {
        Sanctum::actingAs($this->user);

        $room = Room::factory()->create(['available_rooms' => 5]);
        $bookRes = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [['full_name' => 'Budi Santoso', 'gender' => 'male']],
        ])->assertStatus(201);

        $code = $bookRes->json('data.booking_code');

        // Other user attempts to view
        Sanctum::actingAs($this->otherUser);

        $this->getJson("/api/v1/bookings/{$code}")
            ->assertStatus(404);

        $this->postJson("/api/v1/bookings/{$code}/cancel")
            ->assertStatus(404);
    }

    public function test_user_can_cancel_pending_booking_and_inventory_is_restored(): void
    {
        Sanctum::actingAs($this->user);

        $room = Room::factory()->create([
            'available_rooms' => 5,
            'status' => 'available',
        ]);

        $bookRes = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'room_count' => 2,
            'guest_count' => 2,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
                ['full_name' => 'Dewi Lestari', 'gender' => 'female'],
            ],
        ])->assertStatus(201);

        $code = $bookRes->json('data.booking_code');

        // Available rooms decreased to 3
        $this->assertEquals(3, $room->fresh()->available_rooms);

        // Cancel booking
        $this->postJson("/api/v1/bookings/{$code}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        // Available rooms restored to 5
        $this->assertEquals(5, $room->fresh()->available_rooms);
    }
}
