<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_successfully()
    {
        // Fake the mail queue to avoid sending actual emails during tests
        Queue::fake();
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, Country',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
                'phone',
                'address'
            ],
            'access_token',
            'token_type'
        ]);

        $response->assertJson([
            'message' => 'User registered successfully',
            'token_type' => 'Bearer'
        ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, Country'
        ]);

        // Verify password is hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function registration_validates_required_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'name',
                'email',
                'password'
            ]
        ]);

        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function registration_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registration_validates_password_confirmation()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function registration_validates_email_format()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registration_validates_password_minimum_length()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'user' => [
                'id',
                'name',
                'email',
                'phone',
                'address'
            ],
            'access_token',
            'token_type'
        ]);

        $response->assertJson([
            'message' => 'Login successful',
            'token_type' => 'Bearer'
        ]);

        $this->assertNotNull($response->json('access_token'));
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'wrong_password'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Invalid credentials'
        ]);
    }

    /** @test */
    public function login_validates_required_fields()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function login_validates_email_format()
    {
        $loginData = [
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logged out successfully'
        ]);
    }

    /** @test */
    public function logout_requires_authentication()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function authenticated_user_can_get_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'phone',
            'address',
            'email_verified_at',
            'created_at',
            'updated_at'
        ]);

        $response->assertJson([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St'
        ]);
    }

    /** @test */
    public function get_profile_requires_authentication()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function registration_creates_access_token()
    {
        Queue::fake();
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $token = $response->json('access_token');
        $this->assertNotNull($token);
        $this->assertIsString($token);

        // Verify token can be used for authenticated requests
        $profileResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $profileResponse->assertStatus(200);
    }

    /** @test */
    public function login_creates_access_token()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200);

        $token = $response->json('access_token');
        $this->assertNotNull($token);
        $this->assertIsString($token);

        // Verify token can be used for authenticated requests
        $profileResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $profileResponse->assertStatus(200);
    }

    /** @test */
    public function registration_handles_optional_fields()
    {
        Queue::fake();
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
            // phone and address are optional
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => null,
            'address' => null
        ]);
    }

    /** @test */
    public function registration_validates_field_lengths()
    {
        $userData = [
            'name' => str_repeat('A', 300), // Too long
            'email' => str_repeat('A', 250) . '@example.com', // Too long
            'phone' => str_repeat('1', 25), // Too long
            'address' => str_repeat('A', 600), // Too long
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'phone', 'address']);
    }
}
