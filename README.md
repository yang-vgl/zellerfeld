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

## Tech Stack & Library Choices

- **Laravel 11** — framework, migrations, Eloquent ORM, validation, testing.
- **Lighthouse** (`nuwave/lighthouse`) — schema-first GraphQL server for Laravel. Chosen because it maps GraphQL fields directly to Eloquent via directives (`@find`, `@paginate`, `@hasMany`, `@belongsTo`, `@create`, `@validator`, `@rename`), keeps the schema readable, and handles validation + eager loading automatically.
- **SQLite** — zero-config file database, as required by the task.
- **Docker (PHP-FPM + Nginx)** — reproducible dev environment.

## GraphQL Schema

```graphql
type Query {
    userProfile(username: String!): User      # profile + posts
    globalFeed: [Post!]!                      # paginated global feed
}

type Mutation {
    createPost(userId: ID!, body: String!): Post   # max 280 chars
}
```

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

```bash
curl -X POST http://localhost:8001/graphql \
  -H 'Content-Type: application/json' \
  -d '{"query":"{ userProfile(username: \"testuser\") { id username email posts { id body createdAt } } }"}'
```

### Global feed (page 2, 10 per page)

```bash
curl -X POST http://localhost:8001/graphql \
  -H 'Content-Type: application/json' \
  -d '{"query":"{ globalFeed(first: 10, page: 2) { data { id body createdAt user { id username } } paginatorInfo { currentPage lastPage hasMorePages total } } }"}'
```

### Create a post

```bash
curl -X POST http://localhost:8001/graphql \
  -H 'Content-Type: application/json' \
  -d '{"query":"mutation CreatePost { createPost(userId: 1, body: \"Hello from GraphQL!\") { id body createdAt user { id username } } }"}'
```

### If the `posts` table grew past a million rows:

**Database level**

- proper index: single index, combined index
- Mysql partitioning: limitation is the query condition must hit the same field as the partitioning field

**Laravel / application level**
- some optimization like eager loading, cache, rate limiting etc, but this is not much of an application level problem.

**My solution**
- use full text search option like Elasticsearch for searching queries, 
 update query is not a problem, can still happen on mysql, then sync to Elasticsearch.
  Algolia has more features and good UX, but cost more.
