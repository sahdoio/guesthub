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

### User

Manages user profiles and loyalty information. Guests and owners share a single `users` table — owners have a `null` loyalty tier.

| Concept | Class |
|---|---|
| Aggregate | `User` |
| Identity | `UserId` |
| Value Object | `LoyaltyTier` (enum: BRONZE, SILVER, GOLD, PLATINUM) |

Domain events: `UserCreated`, `UserContactInfoUpdated`, `UserLoyaltyTierChanged`.

### IAM (Identity & Access Management)

Handles actors, accounts, hotels, types, authentication, and token management. See [IAM Deep Dive](#iam-deep-dive--actors--authentication) for the full explanation.

| Concept | Class |
|---|---|
| Aggregates | `Actor`, `Account`, `Hotel` |
| Entities | `Type` |
| Identities | `ActorId`, `AccountId`, `TypeId` |
| Value Objects | `TypeName` (enum: SUPERADMIN, OWNER, GUEST), `HashedPassword` |
| Domain Services | `PasswordHasher`, `TokenManager`, `UserGateway` |

Multi-tenant: `Account` serves as the tenant boundary. All actors belong to an account, and other BCs' tables (users, reservations, rooms) carry an `account_id` foreign key. The `actor_types` pivot table links actors to their types.

### Inventory

Manages the hotel's room inventory — room definitions, availability, status, and pricing.

| Concept | Class |
|---|---|
| Aggregate | `Room` |
| Identity | `RoomId` |
| Value Objects | `RoomType` (enum: SINGLE, DOUBLE, SUITE), `RoomStatus` (enum: AVAILABLE, OCCUPIED, MAINTENANCE, OUT_OF_ORDER) |

Room state machine:

```
AVAILABLE ──> OCCUPIED ──> AVAILABLE (release)
    │
    ├──> MAINTENANCE ──> AVAILABLE
    └──> OUT_OF_ORDER ──> AVAILABLE
```

No domain events — state changes are managed through direct commands.

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
┌──────────┐         UserApi              ┌──────────────┐        InventoryApi        ┌─────────────┐
│  User    │  <────────────────────────── │ Reservation  │ ─────────────────────────> │  Inventory  │
│          │   (GuestGateway adapter)     │              │  (InventoryGateway adapter)│             │
│          │                              │              │                            │             │
│          │         UserApi              │              │                            │             │
│          │  <────────────────────────   │              │                            └─────────────┘
└──────────┘   (UserGateway)              └──────────────┘
     ▲
     │  UserApi
     │  (UserGateway adapter)
┌──────────┐
│   IAM    │
│          │
└──────────┘
```

**Relationships:**

| Upstream | Downstream | Pattern | Purpose |
|---|---|---|---|
| User | Reservation | **Anti-Corruption Layer** (read-only via `UserApi`) | Reservation reads user data (name, email, VIP status) via `GuestGateway` |
| User | IAM | **Anti-Corruption Layer** (write via `UserApi`) | IAM creates user profiles during registration via `UserGateway` |
| Inventory | Reservation | **Anti-Corruption Layer** (via `InventoryApi`) | `InventoryGateway` checks room availability and pricing |
| IAM | All BCs | **Sanctum middleware** | `auth:sanctum` protects User and Reservation API routes |

No BC calls another BC's repository directly. All cross-boundary data flows through Gateway adapters and the User BC's `UserApi`.

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
    ├── Http/                   # Inertia view classes, middleware
    ├── Routes/                 # API and web route definitions
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
| `TenantContext` | Singleton holding the current tenant (account) ID for multi-tenant scoping. |
| `BelongsToTenant` | Eloquent global scope that filters queries by `account_id`. |
| `HandleInertiaRequests` | Inertia middleware sharing auth/user data with all pages. |
| `EnsureActorType` | Middleware that validates the authenticated actor has the required type(s). |
| `SetTenantContext` | Middleware that sets the tenant context from the authenticated actor's account. |
| `AuthenticatedUserResolver` | Service resolving the current user's UUID and type from the authenticated actor. |

---

## Domain Events

Domain events are recorded inside aggregates via `recordEvent()` and pulled by application-layer handlers after persistence.

### Reservation Events

| Event | Recorded When | Payload |
|---|---|---|
| `ReservationCreated` | `Reservation::create()` | `reservationId` |
| `ReservationConfirmed` | `confirm()` | `reservationId` |
| `ReservationCancelled` | `cancel()` | `reservationId`, `reason` |
| `GuestCheckedIn` | `checkIn()` | `reservationId`, `roomNumber` |
| `GuestCheckedOut` | `checkOut()` | `reservationId` |
| `SpecialRequestAdded` | `addSpecialRequest()` | `reservationId`, `requestId` |
| `SpecialRequestFulfilled` | `fulfillSpecialRequest()` | `reservationId`, `requestId` |

### User Events

| Event | Recorded When | Payload |
|---|---|---|
| `UserCreated` | `User::create()` | `userId`, `email` |
| `UserContactInfoUpdated` | `updateContactInfo()` | `userId` |
| `UserLoyaltyTierChanged` | `changeLoyaltyTier()` | `userId`, `tier` |

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

### GuestGateway (Reservation → User)

The Reservation BC needs user data (name, email, VIP status) but does not depend on the User domain model. Instead:

1. **Port** — `Reservation/Domain/Service/GuestGateway` interface defines `findByUuid(string): ?GuestInfo`
2. **DTO** — `GuestInfo` is a read-only DTO owned by the Reservation BC
3. **Adapter** — `Reservation/Infrastructure/Integration/GuestGatewayAdapter` delegates to the User BC's `UserApi` and maps to `GuestInfo`

This is an **Anti-Corruption Layer**: the Reservation BC translates User data into its own language (`isVip` is derived from `loyalty_tier`).

**Used by:**
- `CreateReservationHandler` — checks VIP status for booking policy
- `OnReservationConfirmed` — enriches integration event with guest email
- `OnGuestCheckedIn` / `OnGuestCheckedOut` — same enrichment
- `ReservationResource` — includes guest info in API response

### UserGateway (IAM → User)

When an actor registers, the IAM BC creates a corresponding user profile:

1. **Port** — `IAM/Domain/Service/UserGateway` interface defines `create(name, email, phone, document, ?loyaltyTier): int`
2. **Adapter** — `IAM/Infrastructure/Integration/UserGatewayAdapter` delegates to the User BC's `UserApi`

The returned user `id` is stored on the Actor as `userId`.

### User Integration API

The User BC exposes a `UserApi` (in `Infrastructure/Integration/`) for other BCs to consume. It provides:

- `create(name, email, phone, document, ?loyaltyTier): int` — creates a user profile, returns numeric ID
- `findByUuid(string): ?UserData` — returns a `UserData` DTO with profile fields
- `findById(int): ?UserData` — returns a `UserData` DTO by numeric ID

This API is the single entry point for cross-BC access to user data. Both the Reservation and IAM adapters depend on it.

### InventoryGateway (Reservation → Inventory)

Same Anti-Corruption Layer pattern, now backed by the Inventory BC:

1. **Port** — `InventoryGateway` defines `checkAvailability()`, `getRoomTypeInfo()`, `listAvailableRooms()`, and `isRoomAvailable()`
2. **DTOs** — `RoomAvailability`, `RoomTypeInfo`, `AvailableRoom` — owned by the Reservation BC
3. **Adapter** — `InventoryGatewayAdapter` delegates to the Inventory BC's `InventoryApi` and maps to Reservation DTOs

**Used by:**
- `ReservationCreationSpecification` — validates room availability before creation
- `ReservationShowView` — lists available rooms for check-in dropdown
- `CheckInView` / `CheckInAction` — validates the selected room is available before check-in

### Inventory Integration API

The Inventory BC exposes an `InventoryApi` (in `Infrastructure/Integration/`) for other BCs to consume. It provides:

- `findByUuid(string): ?RoomData` — returns room data by UUID
- `findByNumber(string): ?RoomData` — returns room data by room number
- `countAvailableByType(string): int` — counts available rooms of a given type
- `listAvailableByType(string): RoomData[]` — lists available rooms of a given type
- `isRoomAvailable(string): bool` — checks if a specific room number is available

### No Direct Coupling

- No BC imports another BC's domain classes
- No BC calls another BC's repository
- Cross-BC data flows through `UserApi`, `InventoryApi`, and Gateway adapters
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
// UserReflector::reconstruct(...)
$ref = new ReflectionClass(User::class);
$user = $ref->newInstanceWithoutConstructor();

$prop = $ref->getProperty('uuid');
$prop->setValue($user, $uuid);
// ... repeat for each property

return $user;
```

| Reflector | Reconstitutes | Used By |
|---|---|---|
| `RoomReflector` | `Room` | `EloquentRoomRepository` |
| `UserReflector` | `User` | `EloquentUserRepository` |
| `ActorReflector` | `Actor` | `EloquentActorRepository` |
| `AccountReflector` | `Account` | `EloquentAccountRepository` |
| `ReservationReflector` | `Reservation` (with nested `SpecialRequest[]`) | `EloquentReservationRepository` |
| `SpecialRequestReflector` | `SpecialRequest` | `EloquentReservationRepository` (during deserialization) |

Reflectors are **unaffected by private constructors** — they use `ReflectionClass::newInstanceWithoutConstructor()`.

---

## IAM Deep Dive — Actors, Accounts, Types & Authentication

### Multi-Tenancy: Accounts

An **Account** is the IAM aggregate that represents a tenant. Each account is a hotel or organization. All actors belong to an account, and all main tables across BCs (users, reservations, rooms) carry an `account_id` foreign key for data isolation.

### Types

**Type** is a domain entity stored in the `types` table. Types are seeded (`superadmin`, `owner`, `guest`) and referenced by actors via the `actor_types` pivot table (many-to-many). The `TypeName` enum provides type-safe domain logic:

| `TypeName` | Purpose |
|---|---|
| `SUPERADMIN` | System administrator. Can impersonate any hotel owner to manage their properties. |
| `OWNER` | Hotel owner / property manager. Manages rooms, reservations, and guests for their hotel(s). |
| `GUEST` | Hotel guest who accesses the guest portal for reservations and profile management. |

### What is an Actor?

An **Actor** is the IAM aggregate — it represents any identity that can authenticate against the system. Actors have types (not roles) that control access levels.

An Actor belongs to an Account (tenant) and has one or more Types. Each actor has a `userId` linking to a `User` in the User BC.

### The Actor Aggregate

```php
final class Actor extends AggregateRoot
{
    private function __construct(
        public readonly ActorId $uuid,           // identity
        public readonly ?AccountId $accountId,   // tenant reference (null for superadmin)
        /** @var list<TypeId> */
        private(set) array $typeIds,             // types via pivot table
        public readonly string $name,
        public readonly string $email,           // unique, used for login
        public private(set) HashedPassword $password,
        public readonly ?int $userId,            // FK to users table in User BC
        public readonly DateTimeImmutable $createdAt,
        public private(set) ?DateTimeImmutable $updatedAt = null,
    ) {}

    public static function register(...): self { ... }
    public function hasTypeId(TypeId $typeId): bool { ... }
    public function assignType(TypeId $typeId): void { ... }
    public function changePassword(HashedPassword $password): void { ... }
}
```

Key design decisions:
- **`readonly` properties** (uuid, accountId, name, email, userId, createdAt) — set once at registration, never change
- **`private(set)` properties** (typeIds, password, updatedAt) — mutable only through behavior methods
- **`register()` factory** — the only way to create an Actor (constructor is private)
- **`userId` FK** — direct foreign key to `users` table, replacing the old polymorphic `subject_type`/`subject_id`
- **Many-to-many types** — actors can have multiple types via the `actor_types` pivot table

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

**`UserGateway`** — cross-BC user profile creation:
```php
interface UserGateway
{
    public function create(string $name, string $email, string $phone, string $document, ?string $loyaltyTier = null): int;
}
```
Implemented by `UserGatewayAdapter` (in `Infrastructure/Integration/`) which delegates to the User BC's `UserApi`.

### The Dual-Model Approach

IAM has two representations of an actor:

| | `Actor` (Domain) | `ActorModel` (Infrastructure) |
|---|---|---|
| Layer | Domain | Infrastructure (Eloquent) |
| Purpose | Business logic, invariants | Sanctum token issuance, Laravel auth middleware |
| Created by | `Actor::register()` | Seeded or created alongside the domain actor |
| Persistence | `EloquentActorRepository` writes to `actors` table | Reads from the same `actors` table |

Both read/write the same `actors` table. The domain `Actor` is persisted via `EloquentActorRepository` (through `ActorReflector` for hydration). The `ActorModel` is only used by `SanctumTokenManager`, Laravel's `auth:sanctum` middleware, and the web login/register views. It has `BelongsToMany` relationship to `TypeModel` via the `actor_types` pivot table, and a `BelongsTo` relationship to `AccountModel`.

### Token Expiration

Sanctum tokens are configured to expire after 24 hours (configurable via `SANCTUM_TOKEN_EXPIRATION` env var). Web sessions use standard Laravel session expiration. Expired Inertia sessions (419 CSRF errors) are handled by redirecting to `/login` both server-side (via exception handler) and client-side (via Vue global error handler).

### Use Cases (Command Handlers)

**`RegisterActorHandler`** — Registration:
```
1. Check if email already exists → throw ActorAlreadyExistsException
2. Create Account for the new guest
3. Create user profile via UserGateway → returns userId
4. Look up the 'guest' Type
5. Generate new ActorId
6. Hash the plain password via PasswordHasher → HashedPassword
7. Actor::register(...) → creates the aggregate with account, type, userId
8. Save to repository
```

**`RegisterHotelOwnerHandler`** — Owner Registration:
```
1. Create Account for the new owner
2. Create user profile via UserGateway (null loyaltyTier) → returns userId
3. Look up the 'owner' Type
4. Generate new ActorId
5. Hash the plain password via PasswordHasher → HashedPassword
6. Actor::register(...) → creates the aggregate with account, type, userId
7. Save to repository
```
Hotel creation is a separate step done after login inside the dashboard.

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
POST /auth/register      │    Token      │  GET  /users/*               │
  → RegisterActorHandler │ ──────────>   │  POST /reservations/*        │
          │              │  Bearer       │  POST /auth/logout           │
POST /auth/login         │  header       │  ...all other endpoints      │
  → AuthenticateActorHandler             │                              │
          │              │               └──────────────────────────────┘
          └──────────────┘
```

1. **Register** — `POST /api/auth/register` with `{ name, email, password, phone, document }`. Creates an Account, a user profile (via `UserGateway`), and an Actor with `guest` type, returns the actor resource. Also available as web form at `/register`. Validates: email format, password min 8 chars, phone in E.164 format. Rejects duplicate emails.

2. **Login** — `POST /api/auth/login` with `{ email, password }`. Verifies credentials against the domain aggregate, then issues a Sanctum token via `TokenManager`. Returns `{ token, actor_id }`.

3. **Authenticated requests** — include `Authorization: Bearer {token}`. Laravel's `auth:sanctum` middleware validates the token against the `ActorModel` (Eloquent). All User and Reservation routes are protected this way.

4. **Logout** — `POST /api/auth/logout` (authenticated). Revokes all tokens for the actor.

### Middleware

| Middleware | Purpose |
|---|---|
| `EnsureActorType` | Validates actor has required type(s). Used as `type:owner,superadmin` in route groups. |
| `EnsureActorIsOwner` | Validates actor is an owner type. |
| `EnsureActorIsGuest` | Validates actor is a guest type; sets `guest_uuid` on request. |
| `SetTenantContext` | Sets `TenantContext` from the authenticated actor's account. |
| `HandleInertiaRequests` | Shares auth data (actor, user, types) with all Inertia pages. |

### Exceptions

| Exception | Thrown When |
|---|---|
| `ActorAlreadyExistsException` | Registration with an email that's already taken |
| `ActorNotFoundException` | Login with an unknown email |
| `InvalidCredentialsException` | Login with wrong password |
