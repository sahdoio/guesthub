# Architecture

GuestHub is a hotel management system built with **Domain-Driven Design (DDD)** on Laravel. The codebase is organized into **Bounded Contexts (BCs)** under `src/modules/`, each with its own domain model, application layer, and infrastructure.

## Table of Contents

- [Bounded Contexts](#bounded-contexts)
- [Context Map](#context-map)
- [Module Structure](#module-structure)
- [Shared Kernel](#shared-kernel)
- [Domain Events](#domain-events)
- [Integration Events](#integration-events)
- [Event Flow](#event-flow)
- [Inter-BC Communication](#inter-bc-communication)
- [Persistence & Reflectors](#persistence--reflectors)
- [IAM Deep Dive — Actors & Authentication](#iam-deep-dive--actors--authentication)

---

## Bounded Contexts

### Guest

Manages guest profiles and loyalty information.

| Concept | Class |
|---|---|
| Aggregate | `GuestProfile` |
| Identity | `GuestProfileId` |
| Value Object | `LoyaltyTier` (enum: BRONZE, SILVER, GOLD, PLATINUM) |

No domain events — state changes are purely CRUD.

### IAM (Identity & Access Management)

Handles actors, authentication, and token management. See [IAM Deep Dive](#iam-deep-dive--actors--authentication) for the full explanation.

| Concept | Class |
|---|---|
| Aggregate | `Actor` |
| Identity | `ActorId` |
| Value Objects | `ActorType` (enum: GUEST, SYSTEM), `HashedPassword` |
| Domain Services | `PasswordHasher`, `TokenManager`, `GuestProfileGateway` |

No domain events.

### Reservation

The richest BC. Manages the full reservation lifecycle, special requests, and emits domain/integration events.

| Concept | Class |
|---|---|
| Aggregate | `Reservation` |
| Entity | `SpecialRequest` (child of Reservation) |
| Identities | `ReservationId`, `SpecialRequestId` |
| Value Objects | `ReservationStatus`, `ReservationPeriod`, `RequestType`, `RequestStatus` |
| Policy | `ReservationPolicy` |
| Domain Services | `GuestGateway`, `InventoryGateway` |
| DTOs | `GuestInfo`, `RoomAvailability`, `RoomTypeInfo` |

Reservation state machine:

```
PENDING ──> CONFIRMED ──> CHECKED_IN ──> CHECKED_OUT
  │              │
  └──> CANCELLED <┘
```

---

## Context Map

```
┌──────────┐       GuestProfileApi         ┌──────────────┐
│  Guest   │  <─────────────────────────── │ Reservation  │
│          │   (GuestGateway adapter)      │              │
│          │                               │              │
│          │       GuestProfileApi         │              │    stub (future BC)
│          │  <────────────────────────    │              │ <── InventoryGateway
└──────────┘   (GuestProfileGateway)       └──────────────┘
     ▲
     │  GuestProfileApi
     │  (GuestProfileGateway adapter)
┌──────────┐
│   IAM    │
│          │
└──────────┘
```

**Relationships:**

| Upstream | Downstream | Pattern | Purpose |
|---|---|---|---|
| Guest | Reservation | **Anti-Corruption Layer** (read-only via `GuestProfileApi`) | Reservation reads guest data (name, email, VIP status) via `GuestGateway` |
| Guest | IAM | **Anti-Corruption Layer** (write via `GuestProfileApi`) | IAM creates guest profiles during registration via `GuestProfileGateway` |
| (future) Inventory | Reservation | **Anti-Corruption Layer** (stubbed) | `InventoryGateway` checks room availability and pricing |
| IAM | All BCs | **Sanctum middleware** | `auth:sanctum` protects Guest and Reservation API routes |

No BC calls another BC's repository directly. All cross-boundary data flows through Gateway adapters and the Guest BC's `GuestProfileApi`.

---

## Module Structure

Each BC follows the same layered layout:

```
modules/{BC}/
├── Domain/                     # Pure domain — no framework dependencies
│   ├── {Aggregate}.php         # Aggregate root with private constructor + static factory
│   ├── {Aggregate}Id.php       # Identity value object (UUID v7)
│   ├── Entity/                 # Child entities (e.g. SpecialRequest)
│   ├── ValueObject/            # Enums and value objects
│   ├── Event/                  # Domain events (implements DomainEvent)
│   ├── Exception/              # Domain exceptions
│   ├── Repository/             # Repository interface (port)
│   ├── Service/                # Domain service interfaces (ports)
│   ├── Policies/               # Domain policies
│   └── Dto/                    # Read-only DTOs for cross-BC data
│
├── Application/                # Use cases — orchestrates domain
│   ├── Command/                # Command DTOs + Handlers
│   ├── Listeners/              # Domain event listeners (transform → integration events)
│   └── Query/                  # Query DTOs + Handlers (if any)
│
└── Infrastructure/             # Framework adapters
    ├── Persistence/            # Repository implementations, Reflectors, Migrations, Eloquent models
    ├── Http/                   # Controllers, Form Requests, Resources
    ├── Routes/                 # API route definitions
    ├── Services/               # Framework service implementations (e.g. BcryptPasswordHasher)
    ├── Integration/            # Anti-corruption layer adapters for other BCs
    ├── IntegrationEvent/       # Integration event classes
    ├── Messaging/              # Event publishers
    └── Providers/              # Service provider (DI bindings, event wiring, migrations, routes)
```

---

## Shared Kernel

`modules/Shared/` contains base abstractions used by all BCs.

### Domain Layer

| Class | Purpose |
|---|---|
| `ValueObject` | Abstract base. Requires `equals(ValueObject): bool`. |
| `Identity` | Extends `ValueObject`. UUID v7 generation, validation, `fromString()`, `__toString()`. |
| `Entity` | Abstract base. Requires `id(): Identity`. Provides `equals(Entity): bool` by identity. |
| `AggregateRoot` | Extends `Entity`. Manages domain events: `recordEvent()` and `pullDomainEvents()`. |
| `DomainEvent` | Interface. Single method: `occurredOn(): DateTimeImmutable`. |
| `PaginatedResult<T>` | Generic readonly DTO for paginated queries. |

### Application Layer

| Class | Purpose |
|---|---|
| `EventDispatcher` | Interface. Single method: `dispatch(object $event): void`. |
| `EventDispatchingHandler` | Abstract base for command handlers that dispatch domain events after persistence. Provides `dispatchEvents(AggregateRoot)`. |
| `IntegrationEvent` | Interface. Methods: `occurredAt(): DateTimeImmutable`, `toArray(): array`. |

### Infrastructure Layer

| Class | Purpose |
|---|---|
| `LaravelEventDispatcher` | Implements `EventDispatcher` by delegating to Laravel's `Illuminate\Contracts\Events\Dispatcher`. |

---

## Domain Events

Domain events are recorded inside aggregates via `recordEvent()` and pulled by application-layer handlers after persistence. Only the Reservation BC emits domain events.

| Event | Recorded When | Payload |
|---|---|---|
| `ReservationCreated` | `Reservation::create()` | `reservationId` |
| `ReservationConfirmed` | `confirm()` | `reservationId` |
| `ReservationCancelled` | `cancel()` | `reservationId`, `reason` |
| `GuestCheckedIn` | `checkIn()` | `reservationId`, `roomNumber` |
| `GuestCheckedOut` | `checkOut()` | `reservationId` |
| `SpecialRequestAdded` | `addSpecialRequest()` | `reservationId`, `requestId` |
| `SpecialRequestFulfilled` | `fulfillSpecialRequest()` | `reservationId`, `requestId` |

---

## Integration Events

Integration events are enriched, serializable versions of domain events meant for cross-BC or external consumption. They carry all the context needed by consumers (no further lookups required).

| Integration Event | Source Domain Event | Extra Data |
|---|---|---|
| `ReservationConfirmedEvent` | `ReservationConfirmed` | guestEmail, roomType, checkIn, checkOut, isVip |
| `ReservationCancelledEvent` | `ReservationCancelled` | roomType, checkIn, checkOut, reason |
| `GuestCheckedInEvent` | `GuestCheckedIn` | roomNumber, guestEmail, isVip |
| `GuestCheckedOutEvent` | `GuestCheckedOut` | roomNumber, guestEmail |

All integration events implement `IntegrationEvent` (with `occurredAt()` and `toArray()`).

---

## Event Flow

The full lifecycle of an event from aggregate to integration:

```
1. Aggregate behavior method
   │  $this->recordEvent(new ReservationConfirmed($this->uuid))
   ▼
2. Command Handler (Application layer)
   │  $reservation->confirm();
   │  $this->repository->save($reservation);
   │  foreach ($reservation->pullDomainEvents() as $event)
   │      $this->dispatcher->dispatch($event);
   ▼
3. LaravelEventDispatcher
   │  Delegates to Laravel's event system
   ▼
4. Listener (Application layer)
   │  OnReservationConfirmed::handle(ReservationConfirmed $event)
   │  - Fetches reservation from repository (for full context)
   │  - Fetches guest info via GuestGateway (for email, VIP status)
   │  - Creates ReservationConfirmedEvent (integration event)
   │  - Dispatches integration event
   ▼
5. Integration event is dispatched
   (Currently: logged. Future: message broker, webhooks, etc.)
```

**Event wiring** is done in `ReservationServiceProvider::boot()`:

```php
Event::listen(ReservationConfirmed::class, OnReservationConfirmed::class);
Event::listen(ReservationCancelled::class, OnReservationCancelled::class);
Event::listen(GuestCheckedIn::class, OnGuestCheckedIn::class);
Event::listen(GuestCheckedOut::class, OnGuestCheckedOut::class);
```

Note: `ReservationCreated`, `SpecialRequestAdded`, and `SpecialRequestFulfilled` are recorded but have no listeners yet — they exist for future consumers.

---

## Inter-BC Communication

### GuestGateway (Reservation → Guest)

The Reservation BC needs guest data (name, email, VIP status) but does not depend on the Guest domain model. Instead:

1. **Port** — `Reservation/Domain/Service/GuestGateway` interface defines `findByUuid(string): ?GuestInfo`
2. **DTO** — `GuestInfo` is a read-only DTO owned by the Reservation BC
3. **Adapter** — `Reservation/Infrastructure/Integration/GuestGatewayAdapter` delegates to the Guest BC's `GuestProfileApi` and maps to `GuestInfo`

This is an **Anti-Corruption Layer**: the Reservation BC translates Guest data into its own language (`isVip` is derived from `loyalty_tier`).

**Used by:**
- `CreateReservationHandler` — checks VIP status for booking policy
- `OnReservationConfirmed` — enriches integration event with guest email
- `OnGuestCheckedIn` / `OnGuestCheckedOut` — same enrichment
- `ReservationResource` — includes guest info in API response

### GuestProfileGateway (IAM → Guest)

When an actor registers, the IAM BC creates a corresponding guest profile:

1. **Port** — `IAM/Domain/Service/GuestProfileGateway` interface defines `create(name, email, phone, document): string`
2. **Adapter** — `IAM/Infrastructure/Integration/GuestProfileGatewayAdapter` delegates to the Guest BC's `GuestProfileApi`

The returned guest profile UUID is stored on the Actor as `profileId`.

### Guest Integration API

The Guest BC exposes a `GuestProfileApi` (in `Infrastructure/Integration/`) for other BCs to consume. It provides:

- `create(name, email, phone, document): string` — creates a guest profile, returns UUID
- `findByUuid(string): ?GuestProfileData` — returns a `GuestProfileData` DTO with profile fields

This API is the single entry point for cross-BC access to guest data. Both the Reservation and IAM adapters depend on it.

### InventoryGateway (Reservation → future Inventory BC)

Same pattern, currently stubbed with hardcoded data:

1. **Port** — `InventoryGateway` defines `checkAvailability()` and `getRoomTypeInfo()`
2. **Adapter** — `InventoryGatewayAdapter` returns static data (always available)

**Used by:** `ReservationPolicy` — validates room availability before creation.

### No Direct Coupling

- No BC imports another BC's domain classes
- No BC calls another BC's repository
- Cross-BC data flows through `GuestProfileApi` and Gateway adapters
- IAM protects routes via Sanctum middleware (framework-level, not a domain dependency)

---

## Persistence & Reflectors

### Why Reflectors?

Aggregates have **private constructors** and static factory methods that enforce invariants and record domain events. When reconstituting an aggregate from the database, we must:

1. Skip the constructor (avoid re-recording events or re-validating)
2. Set `readonly` and `private(set)` properties with persisted values

### How They Work

Each aggregate has a corresponding Reflector class in `Infrastructure/Persistence/`:

```php
// GuestProfileReflector::reconstruct(...)
$ref = new ReflectionClass(GuestProfile::class);
$profile = $ref->newInstanceWithoutConstructor();

$prop = $ref->getProperty('uuid');
$prop->setValue($profile, $uuid);
// ... repeat for each property

return $profile;
```

| Reflector | Reconstitutes | Used By |
|---|---|---|
| `GuestProfileReflector` | `GuestProfile` | `EloquentGuestProfileRepository` |
| `ActorReflector` | `Actor` | `EloquentActorRepository` |
| `ReservationReflector` | `Reservation` (with nested `SpecialRequest[]`) | `EloquentReservationRepository` |
| `SpecialRequestReflector` | `SpecialRequest` | `EloquentReservationRepository` (during deserialization) |

Reflectors are **unaffected by private constructors** — they use `ReflectionClass::newInstanceWithoutConstructor()`.

---

## IAM Deep Dive — Actors & Authentication

### What is an Actor?

An **Actor** is the IAM aggregate — it represents any identity that can authenticate against the system. The system deliberately avoids calling this "User" because the domain has two distinct actor types with different semantics:

| `ActorType` | Purpose | `profileType` / `profileId` |
|---|---|---|
| `GUEST` | A hotel guest who can manage their own reservations | `profileType: "guest"`, `profileId` points to a `GuestProfile` in the Guest BC |
| `SYSTEM` | Staff, admin, or automated services (booking engine, etc.) | Both `null` |

An Actor owns credentials (email + hashed password) and can hold API tokens. The `profileType` + `profileId` pair is a polymorphic soft link — plain strings, not a foreign key or domain reference. The IAM BC depends on the Guest BC only through the `GuestProfileGateway` port (used during registration to create the guest profile).

### The Actor Aggregate

```php
final class Actor extends AggregateRoot
{
    private function __construct(
        public readonly ActorId $uuid,          // identity
        public readonly ActorType $type,        // guest | system
        public readonly string $name,
        public readonly string $email,          // unique, used for login
        public private(set) HashedPassword $password,  // mutable via changePassword()
        public readonly ?string $profileType,          // polymorphic type (e.g. "guest")
        public readonly ?string $profileId,            // soft link to profile in another BC
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function register(...): self { ... }
    public function changePassword(HashedPassword $password): void { ... }
}
```

Key design decisions:
- **`readonly` properties** (uuid, type, name, email, profileType, profileId, createdAt) — set once at registration, never change
- **`private(set)` properties** (password, updatedAt) — mutable only through behavior methods
- **`register()` factory** — the only way to create an Actor (constructor is private)
- **No domain events** — registration and password changes don't need event-driven side effects (yet)

### Domain Service Ports

The Actor aggregate never touches framework code. Three domain service interfaces define the ports:

**`PasswordHasher`** — hashing and verification:
```php
interface PasswordHasher
{
    public function hash(string $plainPassword): HashedPassword;
    public function verify(string $plainPassword, HashedPassword $hashedPassword): bool;
}
```
Implemented by `BcryptPasswordHasher` (in `Infrastructure/Services/`) using Laravel's `Hash` facade.

**`TokenManager`** — token creation and revocation:
```php
interface TokenManager
{
    public function createToken(string $email, string $tokenName = 'api'): string;
    public function revokeAllTokens(string $email): void;
}
```
Implemented by `SanctumTokenManager` (in `Infrastructure/Services/`). This is the one place where the Eloquent `ActorModel` is used — Sanctum needs an Authenticatable model to issue tokens. The `ActorModel` exists solely for this infrastructure concern; the domain layer never sees it.

**`GuestProfileGateway`** — cross-BC guest profile creation:
```php
interface GuestProfileGateway
{
    public function create(string $name, string $email, string $phone, string $document): string;
}
```
Implemented by `GuestProfileGatewayAdapter` (in `Infrastructure/Integration/`) which delegates to the Guest BC's `GuestProfileApi`.

### The Dual-Model Approach

IAM has two representations of an actor:

| | `Actor` (Domain) | `ActorModel` (Infrastructure) |
|---|---|---|
| Layer | Domain | Infrastructure (Eloquent) |
| Purpose | Business logic, invariants | Sanctum token issuance, Laravel auth middleware |
| Created by | `Actor::register()` | Seeded or created alongside the domain actor |
| Persistence | `EloquentActorRepository` writes to `actors` table | Reads from the same `actors` table |

Both read/write the same `actors` table. The domain `Actor` is persisted via `EloquentActorRepository` (through `ActorReflector` for hydration). The `ActorModel` is only used by `SanctumTokenManager` and Laravel's `auth:sanctum` middleware.

### Use Cases (Command Handlers)

**`RegisterActorHandler`** — Registration:
```
1. Check if email already exists → throw ActorAlreadyExistsException
2. Create guest profile via GuestProfileGateway → returns profileId
3. Generate new ActorId
4. Hash the plain password via PasswordHasher → HashedPassword
5. Actor::register(...) → creates the aggregate (type: GUEST, profileType: "guest", profileId)
6. Save to repository
```

**`AuthenticateActorHandler`** — Login:
```
1. Find actor by email → throw ActorNotFoundException
2. Verify plain password against stored hash via PasswordHasher → throw InvalidCredentialsException
3. Create Sanctum token via TokenManager
4. Return { token, actor_id }
```

**`RevokeTokenHandler`** — Logout:
```
1. Revoke all tokens for the actor's email via TokenManager
```

### Authentication Flow

```
               Public                          Protected (auth:sanctum)
          ┌──────────────┐               ┌──────────────────────────────┐
          │              │               │                              │
POST /auth/register      │    Token      │  GET  /guests/*              │
  → RegisterActorHandler │ ──────────>   │  POST /reservations/*        │
          │              │  Bearer       │  POST /auth/logout           │
POST /auth/login         │  header       │  ...all other endpoints      │
  → AuthenticateActorHandler             │                              │
          │              │               └──────────────────────────────┘
          └──────────────┘
```

1. **Register** — `POST /api/auth/register` with `{ name, email, password, phone, document }`. Creates a guest profile (via `GuestProfileGateway`) and an Actor (always type `GUEST`), returns the actor resource. Validates: email format, password min 8 chars, phone in E.164 format. Rejects duplicate emails.

2. **Login** — `POST /api/auth/login` with `{ email, password }`. Verifies credentials against the domain aggregate, then issues a Sanctum token via `TokenManager`. Returns `{ token, actor_id }`.

3. **Authenticated requests** — include `Authorization: Bearer {token}`. Laravel's `auth:sanctum` middleware validates the token against the `ActorModel` (Eloquent). All Guest and Reservation routes are protected this way.

4. **Logout** — `POST /api/auth/logout` (authenticated). Revokes all tokens for the actor.

### Exceptions

| Exception | Thrown When |
|---|---|
| `ActorAlreadyExistsException` | Registration with an email that's already taken |
| `ActorNotFoundException` | Login with an unknown email |
| `InvalidCredentialsException` | Login with wrong password |
