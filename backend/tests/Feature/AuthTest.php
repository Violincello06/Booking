<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test successful user registration.
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '081234567890',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi berhasil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'role',
                        'created_at',
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'role' => 'user',
        ]);
    }

    /**
     * Test registration fails when email already exists.
     */
    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Duplicate User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration fails with unconfirmed or short password.
     */
    public function test_register_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login.test@example.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'login.test@example.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);
    }

    /**
     * Test login fails with invalid password.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'wrong.pass@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'wrong.pass@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Login gagal',
            ])
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test authenticated user can access their profile.
     */
    public function test_authenticated_user_can_access_me_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Me User',
            'email' => 'me@example.com',
            'phone' => '089999999999',
            'role' => 'user',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Me User',
                    'email' => 'me@example.com',
                    'phone' => '089999999999',
                    'role' => 'user',
                ],
            ])
            ->assertJsonMissing(['password', 'remember_token']);
    }

    /**
     * Test unauthenticated user receives 401.
     */
    public function test_unauthenticated_user_receives_401(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    /**
     * Test user can logout and revoke active token.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('logout_test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil',
            ]);

        // Token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'logout_test_token',
        ]);
    }

    /**
     * Test user role cannot access admin dashboard (403 Forbidden).
     */
    public function test_user_role_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('user_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
            ]);
    }

    /**
     * Test admin role can access admin dashboard (200 OK).
     */
    public function test_admin_role_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Akses admin berhasil diverifikasi',
                'data' => [
                    'role' => 'admin',
                ],
            ]);
    }

    /**
     * Test staff role can access staff operations, but cannot access admin dashboard.
     */
    public function test_staff_role_permissions(): void
    {
        $staff = User::factory()->staff()->create();
        $token = $staff->createToken('staff_token')->plainTextToken;

        // Staff can access staff operations
        $staffResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/staff/operations');

        $staffResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'role' => 'staff',
                ],
            ]);

        // Staff cannot access admin dashboard
        $adminResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/dashboard');

        $adminResponse->assertStatus(403);
    }
}
