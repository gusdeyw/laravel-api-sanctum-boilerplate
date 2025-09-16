<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function authenticate()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function test_authenticated_user_can_list_users()
    {
        $auth = $this->authenticate();
        User::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'address',
                            'email_verified_at',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ]
            ]);
    }

    public function test_authenticated_user_can_create_user()
    {
        $auth = $this->authenticate();

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'password' => 'password123',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'address' => $userData['address'],
        ]);
    }

    public function test_authenticated_user_can_show_user()
    {
        $auth = $this->authenticate();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->getJson("/api/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'phone' => $targetUser->phone,
                    'address' => $targetUser->address,
                ]
            ]);
    }

    public function test_authenticated_user_can_update_user()
    {
        $auth = $this->authenticate();
        $targetUser = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1234567890',
            'address' => '123 Updated Street',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'phone' => '+1234567890',
                    'address' => '123 Updated Street',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1234567890',
            'address' => '123 Updated Street',
        ]);
    }

    public function test_authenticated_user_can_delete_other_users()
    {
        $auth = $this->authenticate();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);
    }

    public function test_user_cannot_delete_themselves()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->deleteJson("/api/users/{$auth['user']->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You cannot delete your own account',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $auth['user']->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_user_crud_endpoints()
    {
        $user = User::factory()->create();

        // Test all CRUD endpoints without authentication
        $this->getJson('/api/users')->assertStatus(401);
        $this->postJson('/api/users', [])->assertStatus(401);
        $this->getJson("/api/users/{$user->id}")->assertStatus(401);
        $this->putJson("/api/users/{$user->id}", [])->assertStatus(401);
        $this->deleteJson("/api/users/{$user->id}")->assertStatus(401);
    }

    public function test_user_creation_validation()
    {
        $auth = $this->authenticate();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_update_validation()
    {
        $auth = $this->authenticate();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/users/{$targetUser->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_update_password()
    {
        $auth = $this->authenticate();
        $targetUser = User::factory()->create();

        $updateData = [
            'password' => 'newpassword123',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token'],
        ])->putJson("/api/users/{$targetUser->id}", $updateData);

        $response->assertStatus(200);

        // Verify password was updated by attempting login
        $targetUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $targetUser->password));
    }
}
