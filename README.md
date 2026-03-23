# [GuestHub](https://github.com/sahdoio/guesthub)

[![Watch the video](https://img.youtube.com/vi/dwifp5zka0g/maxresdefault.jpg)](https://youtu.be/dwifp5zka0g)

A stay management platform built to explore Domain-Driven Design with Laravel. The goal is to model real hospitality operations — stays, reservations, billing, user profiles — as isolated bounded contexts that communicate through well-defined boundaries. Think Airbnb-style: stays can be hotel rooms, houses, or apartments.

This is a learning project. The focus is on getting the architecture right: aggregates with real invariants, value objects, domain events, and repositories that don't leak infrastructure into the domain.

## Documentation

- [Architecture](docs/architecture.md) — Context map, bounded contexts, event flows, inter-BC communication, persistence strategy, and IAM deep dive.
- [Stay Domain Model](docs/stay-domain-model.md) — Domain model reference covering aggregates, entities, value objects, and bounded context boundaries.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS
- **API:** PSR-7 HTTP actions (API), Laravel Request + Inertia (web views)
- **Payments:** Stripe (payment intents, webhooks)
- **Database:** PostgreSQL (Docker), SQLite (tests)
- **CI:** GitHub Actions — tests, code style (Pint), static analysis (PHPStan)

## Overall Architecture

The project is organized into bounded contexts under `src/modules/`. Each module is self-contained with its own domain, application, infrastructure, and presentation layers.

```
modules/
├── IAM/             # BC — Identity & access management (actors, accounts, users, auth, tokens)
├── Stay/            # BC — Stays (properties) and reservations (lifecycle, special requests)
├── Billing/         # BC — Invoices, payments, Stripe integration
└── Shared/          # Shared kernel (Identity, AggregateRoot, ValueObject) + portal views + middleware + seeders
```

### Layers

Each module follows the same structure:

- **Domain** — Aggregates, entities, value objects, repository interfaces, domain events. No framework dependencies.
- **Application** — Commands, queries, and their handlers. Orchestrates domain operations. Handlers return DTOs.
- **Infrastructure** — Repository implementations (Eloquent-based), persistence, service providers, routes, HTTP actions, Inertia view classes, integration adapters.
- **Presentation** — PSR-7 API actions, presenters. Thin layer that delegates to application handlers.

### Key decisions

- **Laravel as infrastructure, not architecture.** The domain layer has zero framework imports. Laravel lives in the infrastructure and presentation layers only.
- **UUID v7 + auto-increment.** Tables use auto-increment `id` as PK for joins and indexing, `uuid` column for external identity. Domain only sees UUIDs.
- **Eloquent for persistence, not for domain.** Eloquent models live in infrastructure and handle table mapping. Domain aggregates are reconstructed via reflection to avoid calling constructors (which record domain events).
- **Command/Query separation.** Write operations go through `Command/` + `Handler/`. Read operations (like listing) go through `Query/`.
- **Anti-Corruption Layer for cross-BC communication.** BCs never import each other's domain classes. All cross-boundary data flows through Gateway interfaces, adapters, and Integration APIs.
- **Single `users` table.** Guests and owners share one table inside the IAM module. Owners have a `null` loyalty tier. Actor types (superadmin, owner, guest) control access, not separate tables.
- **Event-driven Billing.** The Billing BC listens to Stay integration events (reservation confirmed, checked out, cancelled) to create and manage invoices automatically.

## Bounded Contexts

### IAM (Identity & Access Management)

Handles actor registration, authentication, user profile management, and token management. The IAM module owns both the `actors` and `users` tables. Registering an actor automatically creates a user profile. Actors have types (superadmin, owner, guest) stored in the `actor_type_pivot` table. Each actor has a `user_id` foreign key linking to the users table.

The `UserApi` is exposed as an integration API for other BCs (Stay, Billing) to read user data without coupling to IAM's domain.

```
POST   /api/auth/register   → create actor + user profile, returns actor resource
POST   /api/auth/login      → authenticate, returns Bearer token
POST   /api/auth/logout     → revoke all tokens (requires auth)
```

User CRUD (owner dashboard):
```
GET    /api/users           → list (paginated)
GET    /api/users/{uuid}    → show
PUT    /api/users/{uuid}    → update (contact info, loyalty tier, preferences)
DELETE /api/users/{uuid}    → delete
```

Loyalty tiers (guests only): `bronze`, `silver`, `gold`, `platinum`.

### Stay

Manages stays (properties) and the full reservation lifecycle. A Stay is a bookable property with a type (room, entire_space) and category (hotel_room, house, apartment). Reservations are linked to a stay and follow a state machine: create, confirm, check-in, check-out, cancel.

The aggregate enforces state machine transitions — you can't check in without confirming first, can't cancel after check-in, etc. Has child entities (special requests) serialized as JSON, DTOs for cross-BC guest data, and value objects for period and status.

Stay CRUD (owner dashboard):
```
POST   /api/stays              → create stay
GET    /api/stays              → list stays (paginated)
GET    /api/stays/{uuid}       → show stay
PUT    /api/stays/{uuid}       → update stay
```

Reservation lifecycle:
```
POST   /api/reservations              → status: pending
POST   /api/reservations/{id}/confirm → status: confirmed
POST   /api/reservations/{id}/check-in → status: checked_in
POST   /api/reservations/{id}/check-out → status: checked_out
POST   /api/reservations/{id}/cancel  → status: cancelled (requires reason)
```

Stay types: `room`, `entire_space`. Stay categories: `hotel_room`, `house`, `apartment`.

### Billing

Manages invoices and payments for reservations. The Invoice aggregate contains line items (calculated from stay price and nights) and payments (via Stripe). Invoices are created automatically when a reservation is confirmed (via integration event listener).

Invoice state machine: `draft` → `issued` → `paid`. Can be `voided` (from draft/issued) or `refunded` (from paid).

The Billing BC communicates with the Stay BC through the `ReservationGateway` ACL to fetch reservation details (stay name, price per night, check-in/check-out dates) when creating invoices.

```
GET    /api/invoices           → list invoices
GET    /api/invoices/{uuid}    → show invoice
POST   /api/invoices/{uuid}/issue  → issue invoice
POST   /api/invoices/{uuid}/void   → void invoice
POST   /api/invoices/{uuid}/refund → refund invoice
POST   /api/invoices/{uuid}/pay    → initiate Stripe payment
```

Payment methods: `card`, `bank_transfer`, `other`. Payment statuses: `pending`, `succeeded`, `failed`, `refunded`.

### Shared

Contains base domain abstractions (AggregateRoot, Entity, ValueObject, Identity, DomainEvent), application-level interfaces (EventDispatcher, IntegrationEvent, EventStore), middleware (HandleInertiaRequests, EnsureActorType, SetTenantContext), portal views (guest-facing), and mass seeders.

## Frontend

The frontend is a Vue 3 SPA served via Inertia.js. Each BC exposes its own web routes and Inertia view classes alongside its API actions.

### Pages

- **Owner Dashboard** — Summary cards (total reservations, stays, today's check-ins/check-outs, revenue) and charts (reservations by status, users by loyalty tier, stays by category). All data comes from dedicated stats query handlers per BC.
- **Users** — Full CRUD: list with pagination, create, show, edit, delete.
- **Stays** — Full CRUD: list with type/category filters, create, show, edit (price/amenities/capacity), delete.
- **Reservations** — Lifecycle management: list with status filters, create (linked to a stay), show with actions (confirm, check-in, check-out, cancel), special requests.
- **Invoices** — List with status filters, show with actions (issue, void, refund), payment initiation.
- **Guest Portal** — Guest-facing portal: dashboard with reservations, stay browsing, profile management, invoice viewing, payment.

### Vite HMR in Docker

Vite is configured with `usePolling: true` for file watching inside Docker containers (bind-mounts don't propagate inotify events).

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

Tests are organized into three levels:
- **Unit** — Domain logic, handlers with mocked repositories
- **Integration** — Repository implementations against SQLite
- **Feature** — Full HTTP request tests (API + Inertia)

### CI

GitHub Actions runs three parallel jobs on every push/PR:
- **Tests** — PHP 8.4, SQLite in-memory
- **Code Style** — Laravel Pint
- **Static Analysis** — PHPStan

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

The Reservation collection is sequenced as a full lifecycle flow — run them in order. The Stay and Invoice collections cover standard CRUD and lifecycle actions.
