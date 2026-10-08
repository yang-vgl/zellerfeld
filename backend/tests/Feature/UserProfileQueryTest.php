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

        $query = <<<'GRAPHQL'
        {
          userProfile(username: "testuser") {
            id
            username
            email
            posts {
              id
              body
              created_at
            }
          }
        }
        GRAPHQL;

        $response = $this->postJson('/graphql', [
            'query' => $query,
        ]);

        $response->assertStatus(200);

        $response->assertJsonPath('data.userProfile.id', (string) $user->id);
        $response->assertJsonPath('data.userProfile.username', 'testuser');
        $this->assertCount(3, $response->json('data.userProfile.posts'));
    }
}
