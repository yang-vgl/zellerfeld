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

        $mutation = /** @lang GraphQL */ <<<'GRAPHQL'
        mutation CreatePost($userId: ID!, $body: String!) {
          createPost(userId: $userId, body: $body) {
            id
            body
            user {
              id
              username
            }
          }
        }
        GRAPHQL;

        $response = $this->graphQL($mutation, [
            'userId' => $user->id,
            'body' => 'Hello from a test mutation',
        ])->assertGraphQLErrorFree();

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

        $mutation = /** @lang GraphQL */ <<<'GRAPHQL'
        mutation CreatePost($userId: ID!, $body: String!) {
          createPost(userId: $userId, body: $body) {
            id
          }
        }
        GRAPHQL;

        $response = $this->graphQL($mutation, [
            'userId' => $user->id,
            'body' => str_repeat('a', 300),
        ]);

        $response->assertGraphQLValidationKeys(['body']);
        $this->assertDatabaseCount('posts', 0);
    }
}
