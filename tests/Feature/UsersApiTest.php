<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create(['email' => 'admin@example.com']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_users()
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_get_all_users()
    {
        Sanctum::actingAs($this->user);

        // Create additional users
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'address',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);

        // Should include all users (original user + admin + 3 created = 5 total)
        $this->assertCount(5, $response->json('data.data'));
    }

    /** @test */
    public function authenticated_user_can_view_specific_user()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'phone' => '+1234567890',
            'address' => '123 Test St'
        ]);

        $response = $this->getJson("/api/users/{$targetUser->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'address',
                'created_at',
                'updated_at'
            ]
        ]);

        $response->assertJson([
            'data' => [
                'id' => $targetUser->id,
                'name' => 'Target User',
                'email' => 'target@example.com',
                'phone' => '+1234567890',
                'address' => '123 Test St'
            ]
        ]);
    }

    /** @test */
    public function viewing_nonexistent_user_returns_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/users/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function authenticated_user_can_create_new_user()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'phone' => '+9876543210',
            'address' => '456 New St',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'address',
                'created_at',
                'updated_at'
            ]
        ]);

        $response->assertJson([
            'data' => [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'phone' => '+9876543210',
                'address' => '456 New St'
            ]
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'phone' => '+9876543210',
            'address' => '456 New St'
        ]);

        // Verify password is hashed
        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue(Hash::check('password123', $newUser->password));
    }

    /** @test */
    public function user_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function user_creation_validates_email_uniqueness()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => 'Test User',
            'email' => $this->user->email, // Using existing email
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_creation_validates_password_confirmation()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function authenticated_user_can_update_existing_user()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1111111111',
            'address' => '789 Updated St'
        ];

        $response = $this->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $targetUser->id,
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'phone' => '+1111111111',
                'address' => '789 Updated St'
            ]
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1111111111',
            'address' => '789 Updated St'
        ]);
    }

    /** @test */
    public function user_update_validates_email_uniqueness()
    {
        Sanctum::actingAs($this->user);

        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'user2@example.com', // Try to use user2's email for user1
        ];

        $response = $this->putJson("/api/users/{$user1->id}", $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_update_allows_same_email()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create(['email' => 'same@example.com']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'same@example.com', // Same email should be allowed
        ];

        $response = $this->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200);
    }

    /** @test */
    public function user_update_can_include_password()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ];

        $response = $this->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200);

        // Verify password was updated
        $updatedUser = User::find($targetUser->id);
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
    }

    /** @test */
    public function user_update_validates_password_confirmation_when_provided()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'password' => 'newpassword123',
            'password_confirmation' => 'different_password'
        ];

        $response = $this->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function authenticated_user_can_delete_user()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$targetUser->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_user_returns_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/users/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function user_cannot_delete_themselves()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/users/{$this->user->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id
        ]);
    }

    /** @test */
    public function users_index_filters_sensitive_information()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);

        foreach ($response->json('data') as $userData) {
            $this->assertArrayNotHasKey('password', $userData);
            $this->assertArrayNotHasKey('email_verified_at', $userData);
            $this->assertArrayNotHasKey('remember_token', $userData);
        }
    }

    /** @test */
    public function users_show_filters_sensitive_information()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create();

        $response = $this->getJson("/api/users/{$targetUser->id}");

        $response->assertStatus(200);

        $userData = $response->json('data');
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('email_verified_at', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }

    /** @test */
    public function user_creation_handles_optional_fields()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
            // phone and address are optional
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'phone' => null,
            'address' => null
        ]);
    }

    /** @test */
    public function user_update_handles_partial_updates()
    {
        Sanctum::actingAs($this->user);

        $targetUser = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '+1234567890'
        ]);

        $updateData = [
            'name' => 'Updated Name'
            // Only updating name, other fields should remain unchanged
        ];

        $response = $this->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
            'email' => 'original@example.com', // Should remain unchanged
            'phone' => '+1234567890' // Should remain unchanged
        ]);
    }

    /** @test */
    public function user_creation_validates_field_lengths()
    {
        Sanctum::actingAs($this->user);

        $userData = [
            'name' => str_repeat('A', 300), // Too long
            'email' => str_repeat('A', 250) . '@example.com', // Too long
            'phone' => str_repeat('1', 25), // Too long
            'address' => str_repeat('A', 600), // Too long
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'phone', 'address']);
    }
}
