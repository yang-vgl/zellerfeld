<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_user_profile_with_posts(): void
    {
        $user = User::factory()
            ->has(Post::factory()->count(3))
            ->create([
                'username' => 'testuser',
            ]);

        $response = $this->graphQL(/** @lang GraphQL */ <<<'GRAPHQL'
        {
          userProfile(username: "testuser") {
            id
            username
            email
            posts {
              id
              body
              createdAt
            }
          }
        }
        GRAPHQL)->assertGraphQLErrorFree();

        $response->assertJsonPath('data.userProfile.id', (string) $user->id);
        $response->assertJsonPath('data.userProfile.username', 'testuser');
        $this->assertCount(3, $response->json('data.userProfile.posts'));
    }
}
