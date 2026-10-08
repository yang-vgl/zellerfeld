# Micro-Blog GraphQL API — Laravel App

This directory contains the Laravel 11 application powering the Micro-Blog GraphQL API.

For full setup instructions, architecture overview, sample queries, and performance notes, see the **[root README](../README.md)**.

## Quick reference

- GraphQL endpoint: `http://localhost:8001/graphql` (when run via Docker Compose)
- Schema: `graphql/schema.graphql`
- Models: `app/Models/User.php`, `app/Models/Post.php`
- Feed query builder: `app/GraphQL/Queries/PostQuery.php`
- Tests: `php artisan test` (in-memory SQLite)
