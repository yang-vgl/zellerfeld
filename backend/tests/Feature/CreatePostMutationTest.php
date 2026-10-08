<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePostMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_post_with_valid_input(): void
    {
        $user = User::factory()->create();

        $mutation = <<<'GRAPHQL'
        mutation CreatePost($user_id: ID!, $body: String!) {
          createPost(user_id: $user_id, body: $body) {
            id
            body
            user {
              id
              username
            }
          }
        }
        GRAPHQL;

        $response = $this->postJson('/graphql', [
            'query' => $mutation,
            'variables' => [
                'user_id' => $user->id,
                'body' => 'Hello from a test mutation',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.createPost.body', 'Hello from a test mutation');
        $response->assertJsonPath('data.createPost.user.id', (string) $user->id);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'body' => 'Hello from a test mutation',
        ]);
    }

    public function test_it_validates_post_body_length(): void
    {
        $user = User::factory()->create();

        $mutation = <<<'GRAPHQL'
        mutation CreatePost($user_id: ID!, $body: String!) {
          createPost(user_id: $user_id, body: $body) {
            id
          }
        }
        GRAPHQL;

        $tooLong = str_repeat('a', 300);

        $response = $this->postJson('/graphql', [
            'query' => $mutation,
            'variables' => [
                'user_id' => $user->id,
                'body' => $tooLong,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['errors']);
        $this->assertDatabaseCount('posts', 0);
    }
}
