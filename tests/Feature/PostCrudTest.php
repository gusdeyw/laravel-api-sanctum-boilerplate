<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_user_can_create_post(): void
    {
        $postData = [
            'title' => 'Test Post Title',
            'description' => 'This is a test post description with more content.',
            'datepost' => '2024-01-15 10:30:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/posts', $postData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'datepost',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => $postData['title'],
                    'description' => $postData['description'],
                    'datepost' => $postData['datepost'],
                    'user' => [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ],
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => $postData['title'],
            'description' => $postData['description'],
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_view_all_posts(): void
    {
        $posts = Post::factory()->count(3)->for($this->user)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'datepost',
                        'user' => [
                            'id',
                            'name',
                            'email',
                        ],
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    public function test_user_can_view_single_post(): void
    {
        $post = Post::factory()->for($this->user)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/posts/' . $post->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'datepost',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'description' => $post->description,
                ]
            ]);
    }

    public function test_user_can_update_own_post(): void
    {
        $post = Post::factory()->for($this->user)->create();

        $updateData = [
            'title' => 'Updated Post Title',
            'description' => 'Updated post description',
            'datepost' => '2024-02-15 14:30:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/posts/' . $post->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $post->id,
                    'title' => $updateData['title'],
                    'description' => $updateData['description'],
                    'datepost' => $updateData['datepost'],
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => $updateData['title'],
            'description' => $updateData['description'],
        ]);
    }

    public function test_user_cannot_update_other_users_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->for($otherUser)->create();

        $updateData = [
            'title' => 'Updated Post Title',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/posts/' . $post->id, $updateData);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_post(): void
    {
        $post = Post::factory()->for($this->user)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/posts/' . $post->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Post deleted successfully'
            ]);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->for($otherUser)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->deleteJson('/api/posts/' . $post->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_posts(): void
    {
        $response = $this->getJson('/api/posts');
        $response->assertStatus(401);

        $response = $this->postJson('/api/posts', [
            'title' => 'Test Post',
            'description' => 'Test Description',
            'datepost' => '2024-01-15 10:30:00',
        ]);
        $response->assertStatus(401);
    }

    public function test_post_creation_requires_validation(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/posts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'datepost']);
    }

    public function test_post_title_cannot_exceed_255_characters(): void
    {
        $longTitle = str_repeat('a', 256);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/posts', [
            'title' => $longTitle,
            'description' => 'Test Description',
            'datepost' => '2024-01-15 10:30:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_user_can_filter_own_posts(): void
    {
        // Create posts for current user
        $userPosts = Post::factory()->count(2)->for($this->user)->create();

        // Create posts for other user
        $otherUser = User::factory()->create();
        Post::factory()->count(3)->for($otherUser)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/posts?my_posts=true');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        foreach ($data as $post) {
            $this->assertEquals($this->user->id, $post['user']['id']);
        }
    }
}
