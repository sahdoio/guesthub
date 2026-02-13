# GuestHub

[![Watch the video](https://img.youtube.com/vi/dwifp5zka0g/maxresdefault.jpg)](https://youtu.be/dwifp5zka0g)

A hotel management system built to explore Domain-Driven Design with Laravel. The goal is to model real hotel operations — reservations, guest profiles, room inventory — as isolated bounded contexts that communicate through well-defined boundaries.

This is a learning project. The focus is on getting the architecture right: aggregates with real invariants, value objects, domain events, and repositories that don't leak infrastructure into the domain.

## Documentation

- [Architecture](docs/architecture.md) — Context map, bounded contexts, event flows, inter-BC communication, persistence strategy, and IAM deep dive.
- [Hotel Domain Model](docs/hotel-domain-model.md) — Domain model reference covering aggregates, entities, value objects, and bounded context boundaries.

## Overall Architecture

The project is organized into bounded contexts under `src/modules/`. Each module is self-contained with its own domain, application, infrastructure, and presentation layers.

```
modules/
├── IAM/             # BC — Identity & access management (actors, auth, tokens)
├── Reservation/     # BC — Reservation lifecycle (complex aggregate)
├── Guest/           # BC — Guest profiles & preferences (simple CRUD)
└── Shared/          # Shared kernel (Identity, AggregateRoot, ValueObject)
```

### Layers

Each module follows the same structure:

- **Domain** — Aggregates, entities, value objects, repository interfaces, domain events. No framework dependencies.
- **Application** — Commands, queries, and their handlers. Orchestrates domain operations.
- **Infrastructure** — Repository implementations (Eloquent-based), persistence, service providers, routes.
- **Presentation** — Controllers, form requests, API resources. Thin layer that delegates to application handlers.

### Key decisions

- **Laravel as infrastructure, not architecture.** The domain layer has zero framework imports. Laravel lives in the infrastructure and presentation layers only.
- **UUID v7 + auto-increment.** Tables use auto-increment `id` as PK for joins and indexing, `uuid` column for external identity. Domain only sees UUIDs.
- **Eloquent for persistence, not for domain.** Eloquent models live in infrastructure and handle table mapping. Domain aggregates are reconstructed via reflection to avoid calling constructors (which record domain events).
- **Command/Query separation.** Write operations go through `Command/` + `Handler/`. Read operations (like listing) go through `Query/`.

## Bounded Contexts

### IAM (Identity & Access Management)

Handles actor registration, authentication, and token management. Registering an actor automatically creates a guest profile in the Guest BC via a gateway adapter.

```
POST   /api/auth/register   → create actor + guest profile, returns actor resource
POST   /api/auth/login      → authenticate, returns Bearer token
POST   /api/auth/logout     → revoke all tokens (requires auth)
```

### Reservation

Manages the full reservation lifecycle: create, confirm, check-in, check-out, cancel. The aggregate enforces state machine transitions — you can't check in without confirming first, can't cancel after check-in, etc.

Has child entities (special requests) serialized as JSON, DTOs for cross-BC guest data, and value objects for period and status. Domain events are dispatched to integration event handlers.

**Flow: happy path**
```
POST   /api/reservations              → status: pending
POST   /api/reservations/{id}/confirm → status: confirmed
POST   /api/reservations/{id}/check-in → status: checked_in (assigns room)
POST   /api/reservations/{id}/check-out → status: checked_out
```

**Flow: cancellation**
```
POST   /api/reservations              → status: pending
POST   /api/reservations/{id}/cancel  → status: cancelled (requires reason)
```

### Guest

Guest profiles and preferences. Simple CRUD — no state machine, no child entities. Guest profiles are created automatically during IAM registration; the HTTP API exposes read, update, and delete operations.

```
GET    /api/guests           → list (paginated)
GET    /api/guests/{uuid}    → show
PUT    /api/guests/{uuid}    → update (contact info, loyalty tier, preferences)
DELETE /api/guests/{uuid}    → delete
```

Loyalty tiers: `bronze`, `silver`, `gold`, `platinum`.

## Setup

### Docker (recommended)

Requirements: Docker and Docker Compose.

```bash
make go
```

This copies `.env.example` to `.env` (if missing), starts the containers, installs Composer dependencies, and runs migrations.

For a full reset (wipes the database volume and seeds):

```bash
make go-hard
```

### Local

Requirements: PHP 8.4, Composer, Node.js.

```bash
cd src
composer setup
```

This installs dependencies, copies `.env`, generates the app key, runs migrations, and builds frontend assets.

### Development

```bash
composer dev
```

Starts the dev server, queue worker, log tail, and Vite concurrently.

### Tests

```bash
make test          # via Docker (with coverage)
# or
cd src && composer test  # locally, SQLite in-memory
```

### Make commands

| Command | Description |
|---|---|
| `make go` | Start containers, install deps, run migrations (copies `.env` if missing) |
| `make go-hard` | Full reset: remove volume, rebuild, seed database |
| `make up` | Start containers in detached mode with build |
| `make down` | Stop and remove containers |
| `make setup` | Install Composer dependencies |
| `make sh` | Open a bash shell in the app container |
| `make test` | Run tests with coverage |
| `make paratest` | Run tests in parallel (10 processes) with coverage |
| `make test-coverage` | Generate HTML coverage report |
| `make db-migrate` | Run database migrations |
| `make db-seed` | Seed the database |
| `make db-rollback` | Rollback last database migration |
| `make db-reset` | Rollback, migrate, and seed |
| `make clear` | Clear all Laravel caches |
| `make logs` | Follow Docker container logs |
| `make log` | Follow Laravel application log |
| `make phpstan` | Run PHPStan static analysis |

## API Collection

Bruno collection files are in `bruno/`. Open the folder in [Bruno](https://www.usebruno.com/) and select the `Local` environment.

The Reservation collection is sequenced as a full lifecycle flow — run them in order. The Guest collection covers standard CRUD.
