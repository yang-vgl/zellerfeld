<?php

namespace App\GraphQL\Queries;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class PostQuery
{
    /**
     * Build the base query for the global feed.
     *
     * This ensures we always eager-load the user to avoid N+1 and
     * order posts by recency.
     *
     * @param  mixed  $root
     * @param  array<string, mixed>  $args
     * @return EloquentBuilder
     */
    public function resolveGlobalFeed($root, array $args): EloquentBuilder
    {
        return Post::query()
            ->with('user')
            ->latest('created_at');
    }
}
