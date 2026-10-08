# Micro-Blog GraphQL API

A backend-only, high-performance GraphQL API for a social microblogging application — a minimal "Twitter clone" built with **Laravel 11**, **Lighthouse GraphQL**, and **SQLite**, running in **Docker**.

## Architecture Overview

```
Request (HTTP/JSON)
      │
      ▼
┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│   Nginx     │────▶│   PHP-FPM    │────▶│    SQLite    │
│  (port 8001)│     │  (port 9000) │     │ (file-based) │
└─────────────┘     └──────┬───────┘     └──────────────┘
                           │
                    Laravel 11 + Lighthouse
                    GraphQL endpoint: /graphql
```

- **`docker-compose.yml`** — two services:
  - `app`: PHP 8.3-FPM (built from `Dockerfile`), runs the Laravel app mounted at `/var/www/html/backend`.
  - `web`: Nginx serving `backend/public`, forwarding PHP to `app:9000`.
- **`backend/graphql/schema.graphql`** — the GraphQL schema (single source of truth for the API).
- **`backend/app/Models/`** — `User` and `Post` Eloquent models with relationships.
- **`backend/app/GraphQL/Queries/PostQuery.php`** — custom query builder for the global feed (ordering + eager loading).

## Tech Stack & Library Choices

- **Laravel 11** — framework, migrations, Eloquent ORM, validation, testing.
- **Lighthouse** (`nuwave/lighthouse`) — schema-first GraphQL server for Laravel. Chosen because it maps GraphQL fields directly to Eloquent via directives (`@find`, `@paginate`, `@hasMany`, `@belongsTo`, `@create`, `@rules`), keeps the schema readable, and handles validation + eager loading automatically.
- **SQLite** — zero-config file database, as required by the task.
- **Docker (PHP-FPM + Nginx)** — reproducible dev environment.

## GraphQL Schema

```graphql
type Query {
    userProfile(username: String!): User      # profile + posts
    globalFeed: [Post!]!                      # paginated global feed
}

type Mutation {
    createPost(user_id: ID!, body: String!): Post   # max 280 chars
}
```

- `User` exposes `posts` via `@hasMany`; `Post` exposes `user` via `@belongsTo`.
- `globalFeed` uses `@paginate(type: "PAGINATOR")` with a custom `builder` that returns `Post::with('user')->latest('created_at')` — this **eager-loads authors and avoids N+1**.
- `createPost` uses `@create` with Laravel `@rules` (`exists:users,id`, `max:280`) — validation runs through Laravel's validator and returns standard GraphQL `errors`.

## Getting Started

Prerequisites: **Docker** + **Docker Compose**.

```bash
# 1. Build & start the stack
docker compose up -d --build

# 2. Install dependencies (first run only)
docker compose run --rm app composer install

# 3. Environment setup
cp backend/.env.example backend/.env
docker compose run --rm app php artisan key:generate

# 4. Create the SQLite file
touch backend/database/database.sqlite

# 5. Run migrations & seed demo data (5 users × 10 posts)
docker compose run --rm app php artisan migrate --seed

# 6. Verify DB connectivity
docker compose run --rm app php artisan db:test-connection
```

The API is now available at **http://localhost:8001/graphql**.

## Testing

```bash
docker compose run --rm app php artisan test
```

Feature tests run against an **in-memory SQLite** database (`phpunit.xml`) and cover:

- `UserProfileQueryTest` — `userProfile` returns the user and their posts.
- `GlobalFeedQueryTest` — `globalFeed` returns correct pagination shape (`data` + `paginatorInfo`).
- `CreatePostMutationTest` — `createPost` persists a post and enforces the 280-char validation.

## Sample Queries

### User profile (with posts)

```graphql
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
```

### Global feed (page 2, 10 per page)

```graphql
{
  globalFeed(first: 10, page: 2) {
    data {
      id
      body
      created_at
      user { id username }
    }
    paginatorInfo {
      currentPage
      lastPage
      hasMorePages
      total
    }
  }
}
```

### Create a post

```graphql
mutation CreatePost {
  createPost(user_id: 1, body: "Hello from GraphQL!") {
    id
    body
    created_at
    user { id username }
  }
}
```

### cURL example

```bash
curl -X POST http://localhost:8001/graphql \
  -H 'Content-Type: application/json' \
  -d '{"query":"{ globalFeed(first: 5) { data { id body user { username } } } }"}'
```
