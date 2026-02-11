# GuestHub

A hotel management system built to explore Domain-Driven Design with Laravel. The goal is to model real hotel operations — reservations, guest profiles, room inventory — as isolated bounded contexts that communicate through well-defined boundaries.

This is a learning project. The focus is on getting the architecture right: aggregates with real invariants, value objects, domain events, and repositories that don't leak infrastructure into the domain.

## Architecture

The project is organized into bounded contexts under `src/modules/`. Each module is self-contained with its own domain, application, infrastructure, and presentation layers.

```
modules/
├── Reservation/     # BC1 — Reservation lifecycle (complex aggregate)
├── Guest/           # BC3 — Guest profiles & preferences (simple CRUD)
└── Shared/          # Shared kernel (Identity, AggregateRoot, BaseRepository)
```

### Layers

Each module follows the same structure:

- **Domain** — Aggregates, entities, value objects, repository interfaces, domain events. No framework dependencies.
- **Application** — Commands, queries, and their handlers. Orchestrates domain operations.
- **Infrastructure** — Repository implementations (QueryBuilder), persistence, service providers, routes.
- **Presentation** — Controllers, form requests, API resources. Thin layer that delegates to application handlers.

### Key decisions

- **Laravel as infrastructure, not architecture.** The domain layer has zero framework imports. Laravel lives in the infrastructure and presentation layers only.
- **UUID v7 + auto-increment.** Tables use auto-increment `id` as PK for joins and indexing, `uuid` column for external identity. Domain only sees UUIDs.
- **No ORM.** Repositories use Laravel's query builder directly. Entities are reconstructed via reflection to avoid calling constructors (which record domain events).
- **BaseRepository for simple aggregates.** The Guest module extends `BaseRepository` to get `findByUuid()`, `save()`, `remove()` for free. The Reservation module implements everything manually because it has complex serialization (embedded JSON, multiple VOs).
- **Command/Query separation.** Write operations go through `Command/` + `Handler/`. Read operations (like listing) go through `Query/`.

## Bounded Contexts

### Reservation

Manages the full reservation lifecycle: create, confirm, check-in, check-out, cancel. The aggregate enforces state machine transitions — you can't check in without confirming first, can't cancel after check-in, etc.

Has child entities (special requests) serialized as JSON, value objects for guest info, email, phone, and reservation period. Domain events are dispatched to integration event handlers.

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

Guest profiles and preferences. Simple CRUD — no state machine, no child entities. This is the showcase for `BaseRepository`: the repository extends it and only defines `tableName()`, `toEntity()`, `toRecord()`, plus custom queries.

```
POST   /api/guests          → create profile
GET    /api/guests           → list (paginated)
GET    /api/guests/{uuid}    → show
PUT    /api/guests/{uuid}    → update (contact info, loyalty tier, preferences)
DELETE /api/guests/{uuid}    → delete
```

Loyalty tiers: `bronze`, `silver`, `gold`, `platinum`.

## Setup

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
composer test
```

Runs on SQLite in-memory. No external services needed.

## Documentation

- [Hotel Domain Model](docs/hotel-domain-model.md) — Domain model reference covering aggregates, entities, value objects, and bounded context boundaries.

## API Collection

Bruno collection files are in `bruno/`. Open the folder in [Bruno](https://www.usebruno.com/) and select the `Local` environment.

The Reservation collection is sequenced as a full lifecycle flow — run them in order. The Guest collection covers standard CRUD.
