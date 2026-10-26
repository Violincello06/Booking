<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
        ]);
    }

    public function test_public_can_browse_destinations_hotels_and_flights(): void
    {
        $destination = Destination::factory()->create(['name' => 'Bali']);
        $hotel = Hotel::factory()->create(['destination_id' => $destination->id, 'name' => 'Grand Bali Resort']);
        $airline = Airline::factory()->create(['name' => 'Garuda Indonesia', 'code' => 'GA']);
        $deptAirport = Airport::factory()->create(['code' => 'CGK', 'name' => 'Soekarno-Hatta']);
        $arrAirport = Airport::factory()->create(['code' => 'DPS', 'name' => 'Ngurah Rai']);
        $flight = Flight::factory()->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $deptAirport->id,
            'arrival_airport_id' => $arrAirport->id,
            'flight_number' => 'GA-401',
        ]);

        $this->getJson('/api/v1/destinations')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Bali');

        $this->getJson("/api/v1/destinations/{$destination->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Bali');

        $this->getJson('/api/v1/hotels')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Grand Bali Resort');

        $this->getJson('/api/v1/flights')
            ->assertStatus(200)
            ->assertJsonPath('data.0.flight_number', 'GA-401');

        $this->getJson('/api/v1/airlines')
            ->assertStatus(200)
            ->assertJsonPath('data.0.code', 'GA');

        $this->getJson('/api/v1/airports')
            ->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_admin_crud(): void
    {
        $this->postJson('/api/v1/admin/destinations', [
            'name' => 'Labuan Bajo',
            'country' => 'Indonesia',
            'city' => 'Manggarai Barat',
        ])->assertStatus(401);
    }

    public function test_regular_user_forbidden_from_admin_crud(): void
    {
        Sanctum::actingAs($this->regularUser);

        $this->postJson('/api/v1/admin/destinations', [
            'name' => 'Labuan Bajo',
            'country' => 'Indonesia',
            'city' => 'Manggarai Barat',
        ])->assertStatus(403);
    }

    public function test_admin_can_perform_destination_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        // CREATE
        $createRes = $this->postJson('/api/v1/admin/destinations', [
            'name' => 'Lombok',
            'country' => 'Indonesia',
            'province' => 'NTB',
            'city' => 'Mataram',
            'description' => 'Pulau Seribu Masjid',
            'status' => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.name', 'Lombok');

        $destId = $createRes->json('data.id');

        // READ
        $this->getJson("/api/v1/admin/destinations/{$destId}")
            ->assertStatus(200)
            ->assertJsonPath('data.city', 'Mataram');

        // UPDATE
        $this->putJson("/api/v1/admin/destinations/{$destId}", [
            'name' => 'Lombok Mandalika',
            'country' => 'Indonesia',
            'city' => 'Praya',
        ])->assertStatus(200)
            ->assertJsonPath('data.name', 'Lombok Mandalika');

        // DELETE
        $this->deleteJson("/api/v1/admin/destinations/{$destId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('destinations', ['id' => $destId]);
    }

    public function test_admin_can_perform_hotel_and_room_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        $destination = Destination::factory()->create();

        // CREATE HOTEL
        $hotelRes = $this->postJson('/api/v1/admin/hotels', [
            'destination_id' => $destination->id,
            'name' => 'Sunset Beach Hotel',
            'address' => 'Jl. Pantai No. 1',
            'city' => 'Kuta',
            'star_rating' => 5,
            'price_per_night' => 1500000,
            'status' => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.name', 'Sunset Beach Hotel');

        $hotelId = $hotelRes->json('data.id');

        // CREATE ROOM
        $roomRes = $this->postJson('/api/v1/admin/rooms', [
            'hotel_id' => $hotelId,
            'room_type' => 'Deluxe Ocean View',
            'price_per_night' => 1500000,
            'capacity' => 2,
            'total_rooms' => 10,
            'available_rooms' => 10,
        ])->assertStatus(201)
            ->assertJsonPath('data.room_type', 'Deluxe Ocean View');

        $roomId = $roomRes->json('data.id');

        // UPDATE ROOM
        $this->putJson("/api/v1/admin/rooms/{$roomId}", [
            'price_per_night' => 1750000,
            'available_rooms' => 8,
        ])->assertStatus(200)
            ->assertJsonPath('data.price_per_night', 1750000);

        // DELETE ROOM
        $this->deleteJson("/api/v1/admin/rooms/{$roomId}")
            ->assertStatus(200);

        // DELETE HOTEL
        $this->deleteJson("/api/v1/admin/hotels/{$hotelId}")
            ->assertStatus(200);
    }

    public function test_admin_can_perform_airline_and_airport_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        // AIRLINE CRUD
        $airlineRes = $this->postJson('/api/v1/admin/airlines', [
            'name' => 'Batik Air',
            'code' => 'ID',
            'country' => 'Indonesia',
            'status' => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'ID');

        $airlineId = $airlineRes->json('data.id');

        $this->putJson("/api/v1/admin/airlines/{$airlineId}", [
            'name' => 'Batik Air Indonesia',
        ])->assertStatus(200)
            ->assertJsonPath('data.name', 'Batik Air Indonesia');

        // AIRPORT CRUD
        $airportRes = $this->postJson('/api/v1/admin/airports', [
            'name' => 'Kertajati International Airport',
            'code' => 'KJT',
            'city' => 'Majalengka',
            'country' => 'Indonesia',
            'status' => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'KJT');

        $airportId = $airportRes->json('data.id');

        $this->deleteJson("/api/v1/admin/airports/{$airportId}")
            ->assertStatus(200);

        $this->deleteJson("/api/v1/admin/airlines/{$airlineId}")
            ->assertStatus(200);
    }

    public function test_admin_can_perform_flight_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        $airline = Airline::factory()->create();
        $deptAirport = Airport::factory()->create();
        $arrAirport = Airport::factory()->create();

        $flightRes = $this->postJson('/api/v1/admin/flights', [
            'airline_id' => $airline->id,
            'departure_airport_id' => $deptAirport->id,
            'arrival_airport_id' => $arrAirport->id,
            'flight_number' => 'GA-999',
            'departure_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'arrival_time' => now()->addDays(2)->addHours(2)->format('Y-m-d H:i:s'),
            'duration' => 120,
            'price' => 1200000,
            'total_seats' => 180,
            'available_seats' => 180,
            'flight_class' => 'economy',
            'status' => 'scheduled',
        ])->assertStatus(201)
            ->assertJsonPath('data.flight_number', 'GA-999');

        $flightId = $flightRes->json('data.id');

        $this->putJson("/api/v1/admin/flights/{$flightId}", [
            'price' => 1350000,
        ])->assertStatus(200)
            ->assertJsonPath('data.price', 1350000);

        $this->deleteJson("/api/v1/admin/flights/{$flightId}")
            ->assertStatus(200);
    }

    public function test_admin_can_perform_voucher_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        $voucherRes = $this->postJson('/api/v1/admin/vouchers', [
            'code' => 'DISC50',
            'name' => 'Diskon Liburan 50%',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'minimum_transaction' => 100000,
            'quota' => 100,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'active',
        ])->assertStatus(201)
            ->assertJsonPath('data.code', 'DISC50');

        $voucherId = $voucherRes->json('data.id');

        $this->deleteJson("/api/v1/admin/vouchers/{$voucherId}")
            ->assertStatus(200);
    }

    public function test_admin_can_perform_user_crud(): void
    {
        Sanctum::actingAs($this->adminUser);

        $userRes = $this->postJson('/api/v1/admin/users', [
            'name' => 'Staff Operasional',
            'email' => 'staff.ops@travelbooking.com',
            'password' => 'StaffPassword123!',
            'role' => 'staff',
            'phone' => '081299887766',
        ])->assertStatus(201)
            ->assertJsonPath('data.role', 'staff');

        $userId = $userRes->json('data.id');

        $this->putJson("/api/v1/admin/users/{$userId}", [
            'name' => 'Staff Operasional Senior',
        ])->assertStatus(200)
            ->assertJsonPath('data.name', 'Staff Operasional Senior');

        $this->deleteJson("/api/v1/admin/users/{$userId}")
            ->assertStatus(200);
    }
}
