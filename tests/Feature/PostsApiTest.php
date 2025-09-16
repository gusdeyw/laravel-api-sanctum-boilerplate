<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_posts()
    {
        $response = $this->getJson('/api/posts');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_get_all_posts()
    {
        Sanctum::actingAs($this->user);

        // Create some posts
        $posts = Post::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function authenticated_user_can_create_post()
    {
        Sanctum::actingAs($this->user);

        $postData = [
            'title' => 'Test Post Title',
            'description' => 'This is the description of the test post.',
            'datepost' => now()->format('Y-m-d H:i:s')
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'description',
                'datepost',
                'created_at',
                'updated_at',
                'user'
            ]
        ]);

        $response->assertJson([
            'data' => [
                'title' => 'Test Post Title',
                'description' => 'This is the description of the test post.'
            ]
        ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'description' => 'This is the description of the test post.',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function post_creation_validates_required_fields()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/posts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'description']);
    }

    /** @test */
    public function post_creation_validates_field_lengths()
    {
        Sanctum::actingAs($this->user);

        $postData = [
            'title' => str_repeat('A', 300), // Too long
            'description' => 'Valid description',
            'datepost' => now()->format('Y-m-d H:i:s')
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function post_creation_validates_date_format()
    {
        Sanctum::actingAs($this->user);

        $postData = [
            'title' => 'Valid Title',
            'description' => 'Valid description',
            'datepost' => 'invalid-date-format'
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['datepost']);
    }

    /** @test */
    public function authenticated_user_can_view_specific_post()
    {
        Sanctum::actingAs($this->user);

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Specific Post Title',
            'description' => 'Specific post description',
            'datepost' => now()->format('Y-m-d H:i:s')
        ]);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'description',
                'status',
                'user_id',
                'created_at',
                'updated_at',
                'user'
            ]
        ]);

        $response->assertJson([
            'data' => [
                'id' => $post->id,
                'title' => 'Specific Post Title',
                'description' => 'Specific post content'
            ]
        ]);
    }

    /** @test */
    public function viewing_nonexistent_post_returns_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/posts/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function authenticated_user_can_update_their_post()
    {
        Sanctum::actingAs($this->user);

        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => 'Updated Post Title',
            'description' => 'Updated post content.',
            'status' => 'draft'
        ];

        $response = $this->putJson("/api/posts/{$post->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $post->id,
                'title' => 'Updated Post Title',
                'description' => 'Updated post content.',
                'status' => 'draft'
            ]
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Post Title',
            'description' => 'Updated post content.',
            'status' => 'draft'
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_post()
    {
        $anotherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $anotherUser->id]);

        Sanctum::actingAs($this->user);

        $updateData = [
            'title' => 'Attempting to update',
            'description' => 'This should fail'
        ];

        $response = $this->putJson("/api/posts/{$post->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function post_update_validates_fields()
    {
        Sanctum::actingAs($this->user);

        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'title' => '', // Empty title
            'description' => '', // Empty content
            'status' => 'invalid_status'
        ];

        $response = $this->putJson("/api/posts/{$post->id}", $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'description', 'status']);
    }

    /** @test */
    public function authenticated_user_can_delete_their_post()
    {
        Sanctum::actingAs($this->user);

        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_users_post()
    {
        $anotherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $anotherUser->id]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id
        ]);
    }

    /** @test */
    public function deleting_nonexistent_post_returns_404()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/posts/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function posts_are_returned_with_user_relationship()
    {
        Sanctum::actingAs($this->user);

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Post'
        ]);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email'
                ]
            ]
        ]);

        $response->assertJson([
            'data' => [
                'user' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email
                ]
            ]
        ]);
    }

    /** @test */
    public function posts_index_includes_user_relationship()
    {
        Sanctum::actingAs($this->user);

        Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function post_status_defaults_to_draft_when_not_provided()
    {
        Sanctum::actingAs($this->user);

        $postData = [
            'title' => 'Test Post Title',
            'description' => 'This is the content of the test post.'
            // status not provided
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'status' => 'draft'
            ]
        ]);
    }

    /** @test */
    public function posts_can_be_filtered_by_status()
    {
        Sanctum::actingAs($this->user);

        // Create posts with different statuses
        Post::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
        Post::factory()->create(['user_id' => $this->user->id, 'status' => 'published']);
        Post::factory()->create(['user_id' => $this->user->id, 'status' => 'draft']);

        $response = $this->getJson('/api/posts?status=published');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $post) {
            $this->assertEquals('published', $post['status']);
        }
    }

    /** @test */
    public function user_can_only_see_their_own_posts()
    {
        $anotherUser = User::factory()->create();

        // Create posts for both users
        Post::factory()->count(2)->create(['user_id' => $this->user->id]);
        Post::factory()->count(3)->create(['user_id' => $anotherUser->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $post) {
            $this->assertEquals($this->user->id, $post['user_id']);
        }
    }
}
