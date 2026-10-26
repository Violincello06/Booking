<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    protected function createHotelBooking(): string
    {
        $destination = Destination::factory()->create();
        $hotel = Hotel::factory()->create(['destination_id' => $destination->id]);
        $room = Room::factory()->create([
            'hotel_id' => $hotel->id,
            'price_per_night' => 500000,
            'available_rooms' => 5,
            'status' => 'available',
        ]);

        $bookRes = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(), // 2 nights = 1.000.000
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        return $bookRes->json('data.booking_code');
    }

    public function test_user_can_checkout_payment_and_receive_instructions(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createHotelBooking();

        $response = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'bca_va',
        ])->assertStatus(201)
          ->assertJsonPath('data.status', 'pending')
          ->assertJsonPath('data.payment_method', 'bca_va')
          ->assertJsonPath('data.amount', 1000000);

        $paymentCode = $response->json('data.payment_code');
        $this->assertStringStartsWith('PAY-', $paymentCode);
        $this->assertNotNull($response->json('data.payment_instructions.va_number'));
    }

    public function test_user_can_simulate_successful_payment_and_booking_becomes_confirmed(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createHotelBooking();

        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'qris',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        // Simulate successful payment
        $simulateRes = $this->postJson("/api/v1/payments/{$paymentCode}/simulate", [
            'action' => 'success',
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'paid')
          ->assertJsonPath('data.booking.status', 'confirmed');

        $this->assertNotNull($simulateRes->json('data.paid_at'));
        $this->assertNotNull($simulateRes->json('data.transaction_reference'));

        // Booking status in database should also be confirmed
        $this->assertDatabaseHas('bookings', [
            'booking_code' => $bookingCode,
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_check_payment_status(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createHotelBooking();

        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'gopay',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        $this->getJson("/api/v1/payments/{$paymentCode}/status")
            ->assertStatus(200)
            ->assertJsonPath('data.payment_code', $paymentCode)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_user_can_simulate_failed_payment(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createHotelBooking();

        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'credit_card',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        $this->postJson("/api/v1/payments/{$paymentCode}/simulate", [
            'action' => 'failed',
        ])->assertStatus(200)
          ->assertJsonPath('data.status', 'failed');
    }

    public function test_user_can_access_booking_confirmation_after_paid(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createHotelBooking();

        // Attempting confirmation before payment should fail
        $this->getJson("/api/v1/bookings/{$bookingCode}/confirmation")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Checkout & Pay
        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'mandiri_va',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        $this->postJson("/api/v1/payments/{$paymentCode}/simulate", [
            'action' => 'success',
        ])->assertStatus(200);

        // Confirmation should now succeed and return full e-voucher details
        $this->getJson("/api/v1/bookings/{$bookingCode}/confirmation")
            ->assertStatus(200)
            ->assertJsonPath('data.booking_code', $bookingCode)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.passengers.0.full_name', 'Budi Santoso');
    }

    public function test_user_cannot_access_other_users_payment(): void
    {
        Sanctum::actingAs($this->user);

        $bookingCode = $this->createHotelBooking();
        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'bni_va',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        // Other user attempts to view or simulate payment
        Sanctum::actingAs($this->otherUser);

        $this->getJson("/api/v1/payments/{$paymentCode}/status")
            ->assertStatus(404);

        $this->postJson("/api/v1/payments/{$paymentCode}/simulate", [
            'action' => 'success',
        ])->assertStatus(404);
    }
}
