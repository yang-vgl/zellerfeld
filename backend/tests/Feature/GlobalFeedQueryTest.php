<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalFeedQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_global_feed_with_users(): void
    {
        // Create 15 posts so we can test pagination (default first: 10)
        Post::factory()
            ->count(15)
            ->for(User::factory(), 'user')
            ->create();

        $query = <<<'GRAPHQL'
        {
          globalFeed(first: 10, page: 1) {
            data {
              id
              body
              created_at
              user {
                id
                username
              }
            }
            paginatorInfo {
              currentPage
              total
              hasMorePages
            }
          }
        }
        GRAPHQL;

        $response = $this->postJson('/graphql', [
            'query' => $query,
        ]);

        $response->assertStatus(200);

        // We should get 10 items on the first page
        $this->assertCount(10, $response->json('data.globalFeed.data'));
        $response->assertJsonPath('data.globalFeed.paginatorInfo.currentPage', 1);
        $response->assertJsonPath('data.globalFeed.paginatorInfo.total', 15);
    }
}
