<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected User $admin;
    protected Hotel $hotel;
    protected Room $room;
    protected Flight $flight;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'user']);
        $this->otherUser = User::factory()->create(['name' => 'Siti Rahma', 'role' => 'user']);
        $this->admin = User::factory()->create(['name' => 'Admin Utama', 'role' => 'admin']);

        $destination = Destination::factory()->create();
        $this->hotel = Hotel::factory()->create(['destination_id' => $destination->id]);
        $this->room = Room::factory()->create([
            'hotel_id' => $this->hotel->id,
            'price_per_night' => 1000000,
            'total_rooms' => 10,
            'available_rooms' => 10,
            'status' => 'available',
        ]);

        $airline = Airline::factory()->create();
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();

        $this->flight = Flight::create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'flight_number' => 'GA-888',
            'departure_time' => now()->addDays(2),
            'arrival_time' => now()->addDays(2)->addHours(2),
            'duration' => 120,
            'price' => 850000,
            'total_seats' => 50,
            'available_seats' => 50,
            'status' => 'scheduled',
        ]);
    }

    public function test_user_receives_notification_when_booking_hotel(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'booking',
        ]);

        $notif = Notification::where('user_id', $this->user->id)->first();
        $this->assertStringContainsString($bookingCode, $notif->message);
    }

    public function test_user_receives_notification_when_booking_flight(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->postJson('/api/v1/bookings/flight', [
            'flight_id' => $this->flight->id,
            'passenger_count' => 1,
            'passengers' => [
                ['full_name' => 'Budi Santoso', 'identity_number' => '3201123456789001', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'booking',
            'title' => 'Pemesanan Tiket Pesawat Berhasil',
        ]);

        $notif = Notification::where('user_id', $this->user->id)->where('type', 'booking')->first();
        $this->assertStringContainsString($bookingCode, $notif->message);
    }

    public function test_user_receives_notification_when_booking_is_cancelled(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        // Cancel
        $this->postJson("/api/v1/bookings/{$bookingCode}/cancel")->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'cancellation',
            'title' => 'Pemesanan Dibatalkan',
        ]);
    }

    public function test_user_receives_notification_when_payment_is_successful(): void
    {
        Sanctum::actingAs($this->user);

        $res = $this->postJson('/api/v1/bookings/hotel', [
            'room_id' => $this->room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'room_count' => 1,
            'guest_count' => 1,
            'guests' => [
                ['full_name' => 'Budi Santoso', 'gender' => 'male'],
            ],
        ])->assertStatus(201);

        $bookingCode = $res->json('data.booking_code');

        // Checkout
        $checkoutRes = $this->postJson('/api/v1/payments/checkout', [
            'booking_code' => $bookingCode,
            'payment_method' => 'bca_va',
        ])->assertStatus(201);

        $paymentCode = $checkoutRes->json('data.payment_code');

        // Simulate success
        $this->postJson("/api/v1/payments/{$paymentCode}/simulate", [
            'action' => 'success',
        ])->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'payment',
            'title' => 'Pembayaran Berhasil Dikonfirmasi',
        ]);
    }

    public function test_user_can_list_notifications_with_unread_count(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Notif 1',
            'read_at' => null,
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Notif 2',
            'read_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 1,
                ],
            ]);

        $this->assertCount(2, $response->json('data.notifications'));
    }

    public function test_user_can_get_unread_count_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 3,
                ],
            ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        Sanctum::actingAs($this->user);

        $notif = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->patchJson("/api/v1/notifications/{$notif->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notif->id,
                    'is_read' => true,
                ],
            ]);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Sanctum::actingAs($this->user);

        Notification::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'updated_count' => 4,
                ],
            ]);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_user_can_delete_their_notification(): void
    {
        Sanctum::actingAs($this->user);

        $notif = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/notifications/{$notif->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus',
            ]);

        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }

    public function test_user_cannot_access_or_modify_another_users_notification(): void
    {
        $otherNotif = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->user);

        // Cannot mark as read
        $this->patchJson("/api/v1/notifications/{$otherNotif->id}/read")
            ->assertStatus(404);

        // Cannot delete
        $this->deleteJson("/api/v1/notifications/{$otherNotif->id}")
            ->assertStatus(404);
    }

    public function test_admin_can_broadcast_notification_to_all_users(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/notifications/broadcast', [
            'title' => 'Flash Sale 10.10 Telah Dimulai!',
            'message' => 'Dapatkan diskon hingga 50% untuk seluruh tiket dan hotel sekarang juga.',
            'type' => 'promo',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Flash Sale 10.10 Telah Dimulai!',
            'type' => 'promo',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->otherUser->id,
            'title' => 'Flash Sale 10.10 Telah Dimulai!',
            'type' => 'promo',
        ]);
    }

    public function test_admin_can_list_system_notifications(): void
    {
        Notification::factory()->count(5)->create();

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }
}
