<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Budi Santoso']);
    }

    protected function createPendingHotelBooking(float $pricePerNight = 500000, int $nights = 2): string
    {
        $destination = Destination::factory()->create();
        $hotel = Hotel::factory()->create(['destination_id' => $destination->id]);
        $room = Room::factory()->create([
            'hotel_id' => $hotel->id,
            'price_per_night' => $pricePerNight,
            'available_rooms' => 10,
            'status' => 'available',
        ]);

        $bookRes = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(2 + $nights)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        return $bookRes->json('data.booking_code');
    }

    public function test_public_can_browse_active_vouchers(): void
    {
        // Active and valid voucher
        Voucher::factory()->create([
            'code' => 'ACTIVE10',
            'name' => 'Diskon 10%',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
            'quota' => 100,
            'used_quota' => 5,
        ]);

        // Expired voucher
        Voucher::factory()->create([
            'code' => 'EXPIRED',
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(5),
            'status' => 'active',
        ]);

        // Inactive voucher
        Voucher::factory()->create([
            'code' => 'INACTIVE',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'inactive',
        ]);

        // Exhausted quota voucher
        Voucher::factory()->create([
            'code' => 'SOLDOUT',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
            'quota' => 10,
            'used_quota' => 10,
        ]);

        $response = $this->getJson('/api/v1/vouchers')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'ACTIVE10');
    }

    public function test_user_can_apply_percentage_voucher_with_max_discount_cap(): void
    {
        Sanctum::actingAs($this->user);

        // 2 nights * 500.000 = 1.000.000
        $bookingCode = $this->createPendingHotelBooking(500000, 2);

        // 50% discount but capped at 200.000
        $voucher = Voucher::factory()->create([
            'code' => 'DISC50CAP',
            'discount_type' => 'percentage',
            'discount_value' => 50,
            'minimum_transaction' => 300000,
            'maximum_discount' => 200000,
            'quota' => 50,
            'used_quota' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $res = $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'DISC50CAP',
            'booking_code' => $bookingCode,
        ])->assertStatus(200)
          ->assertJsonPath('data.voucher_code', 'DISC50CAP')
          ->assertJsonPath('data.discount_amount', 200000)
          ->assertJsonPath('data.original_amount', 1000000)
          ->assertJsonPath('data.final_amount', 800000);

        // Quota should increment
        $this->assertEquals(1, $voucher->fresh()->used_quota);

        // Booking in DB should have total_amount updated to 800.000
        $this->assertDatabaseHas('bookings', [
            'booking_code' => $bookingCode,
            'total_amount' => 800000,
        ]);

        // Voucher usage record created
        $this->assertDatabaseHas('voucher_usages', [
            'voucher_id' => $voucher->id,
            'user_id' => $this->user->id,
            'discount_amount' => 200000,
        ]);
    }

    public function test_user_can_apply_fixed_amount_voucher(): void
    {
        Sanctum::actingAs($this->user);

        // 1 night * 400.000 = 400.000
        $bookingCode = $this->createPendingHotelBooking(400000, 1);

        $voucher = Voucher::factory()->create([
            'code' => 'POTONGAN100',
            'discount_type' => 'fixed',
            'discount_value' => 100000,
            'minimum_transaction' => 200000,
            'quota' => 20,
            'used_quota' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'POTONGAN100',
            'booking_code' => $bookingCode,
        ])->assertStatus(200)
          ->assertJsonPath('data.discount_amount', 100000)
          ->assertJsonPath('data.final_amount', 300000);
    }

    public function test_voucher_fails_if_booking_amount_below_minimum_transaction(): void
    {
        Sanctum::actingAs($this->user);

        // Total 200.000
        $bookingCode = $this->createPendingHotelBooking(200000, 1);

        Voucher::factory()->create([
            'code' => 'MIN500',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'minimum_transaction' => 500000, // Requires 500k
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'MIN500',
            'booking_code' => $bookingCode,
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }

    public function test_voucher_fails_if_expired(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createPendingHotelBooking();

        Voucher::factory()->create([
            'code' => 'EXPIREDVOUCHER',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(2),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'EXPIREDVOUCHER',
            'booking_code' => $bookingCode,
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }

    public function test_voucher_fails_if_quota_exhausted(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createPendingHotelBooking();

        Voucher::factory()->create([
            'code' => 'HABIS',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'quota' => 5,
            'used_quota' => 5, // Fully used
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'HABIS',
            'booking_code' => $bookingCode,
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }

    public function test_cannot_apply_same_voucher_twice_to_same_booking(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createPendingHotelBooking(500000, 2);

        Voucher::factory()->create([
            'code' => 'DOUBLECLAIM',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'minimum_transaction' => 100000,
            'quota' => 50,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        // First application: Success
        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'DOUBLECLAIM',
            'booking_code' => $bookingCode,
        ])->assertStatus(200);

        // Second application on same booking: Fails 422
        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'DOUBLECLAIM',
            'booking_code' => $bookingCode,
        ])->assertStatus(422)
          ->assertJsonPath('success', false);
    }

    public function test_user_can_view_their_voucher_usage_history(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createPendingHotelBooking(500000, 2);

        Voucher::factory()->create([
            'code' => 'HISTORYCHECK',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'minimum_transaction' => 100000,
            'quota' => 50,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/vouchers/apply', [
            'code' => 'HISTORYCHECK',
            'booking_code' => $bookingCode,
        ])->assertStatus(200);

        $this->getJson('/api/v1/vouchers/usages')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.voucher_code', 'HISTORYCHECK');
    }
}
