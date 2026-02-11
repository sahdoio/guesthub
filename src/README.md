# GuestHub

Hotel management system built with DDD (Domain-Driven Design) on Laravel.

## Documentation

- **[Architecture](../docs/architecture.md)** — Bounded Contexts, context map, event flows, inter-BC communication, persistence strategy, IAM deep dive
- **[Domain Model](../docs/hotel-domain-model.md)** — Hotel domain model explanation, BC diagrams, planned Inventory BC

## Bounded Contexts

| BC | Responsibility |
|---|---|
| **Guest** | Guest profiles and loyalty tiers |
| **IAM** | Actor registration, authentication (Sanctum), token management |
| **Reservation** | Reservation lifecycle, special requests, domain/integration events |

## Setup

```bash
cd src
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

## Tests

```bash
php artisan test
```

## API Collection

The [Bruno](https://www.usebruno.com/) collection is in the `bruno/` directory. Import it into Bruno and select the **Local** environment.

**Flow:** Register → Login → (use token) → Create Guest → Create Reservation → lifecycle operations.
