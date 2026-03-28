# Architecture

GuestHub is a stay management platform built with **Domain-Driven Design (DDD)** on Laravel. The codebase is organized into **Bounded Contexts (BCs)** under `src/modules/`, each with its own domain model, application layer, and infrastructure.

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

### IAM (Identity & Access Management)

Handles actors, accounts, users, types, authentication, and token management. The IAM module owns both the `actors` table and the `users` table (user profiles with loyalty tiers). See [IAM Deep Dive](#iam-deep-dive--actors--authentication) for the full explanation.

| Concept | Class |
|---|---|
| Aggregates | `Actor`, `Account`, `User` |
| Entities | `Type` |
| Identities | `ActorId`, `AccountId`, `UserId`, `TypeId` |
| Value Objects | `TypeName` (enum: SUPERADMIN, OWNER, GUEST), `HashedPassword`, `LoyaltyTier` (enum: BRONZE, SILVER, GOLD, PLATINUM) |
| Domain Services | `PasswordHasher`, `TokenManager`, `EmailUniquenessChecker`, `UserEmailUniquenessChecker` |

Multi-tenant: `Account` serves as the tenant boundary. All actors belong to an account, and other BCs' tables (stays, reservations, invoices) carry an `account_id` foreign key. The `actor_type_pivot` table links actors to their types.

Domain events: `UserCreated`, `ActorRegistered`, `AccountCreated`, `UserContactInfoUpdated`, `UserLoyaltyTierChanged`.

Domain exceptions: `UserAlreadyExistsException`, `ActorAlreadyExistsException`, `ActorNotFoundException`, `InvalidCredentialsException`.

### Stay

Manages stays (bookable properties) and the full reservation lifecycle. A Stay is an Airbnb-style property listing with a type and category. Reservations are linked to a stay and enforce a state machine for their lifecycle. Each reservation tracks guest counts (adults, children, babies, pets).

| Concept | Class |
|---|---|
| Aggregates | `Stay`, `Reservation` |
| Entity | `SpecialRequest` (child of Reservation) |
| Identities | `StayId`, `ReservationId`, `SpecialRequestId` |
| Value Objects | `StayType` (enum: ROOM, ENTIRE_SPACE), `StayCategory` (enum: HOTEL_ROOM, HOUSE, APARTMENT), `ReservationStatus`, `ReservationPeriod`, `RequestType`, `RequestStatus` |
| Specification | `ReservationCreationSpecification` |
| Domain Services | `GuestGateway` |
| DTOs | `GuestInfo` |

Reservation guest counts:

| Field | Default | Constraints |
|---|---|---|
| `adults` | 1 | min: 1, max: 20 |
| `children` | 0 | min: 0, max: 20 |
| `babies` | 0 | min: 0, max: 10 |
| `pets` | 0 | min: 0, max: 5 |

Stay types and categories:

| `StayType` | Purpose |
|---|---|
| `ROOM` | A single room within a larger property (e.g., hotel room) |
| `ENTIRE_SPACE` | A complete standalone property (e.g., house, apartment) |

| `StayCategory` | Purpose |
|---|---|
| `HOTEL_ROOM` | Room in a hotel |
| `HOUSE` | Standalone house |
| `APARTMENT` | Apartment unit |

Reservation state machine:

```
PENDING ──> CONFIRMED ──> CHECKED_IN ──> CHECKED_OUT
  │              │
  └──> CANCELLED <┘
```

### Billing

Manages invoices and payments for reservations. The Invoice aggregate contains line items and payments. Invoices are created automatically when Stay integration events fire (reservation confirmed). Payments are processed via Stripe.

| Concept | Class |
|---|---|
| Aggregate | `Invoice` |
| Entities | `LineItem`, `Payment` |
| Identities | `InvoiceId`, `LineItemId`, `PaymentId` |
| Value Objects | `Money`, `InvoiceStatus` (enum: DRAFT, ISSUED, PAID, VOID, REFUNDED), `PaymentStatus` (enum: PENDING, SUCCEEDED, FAILED, REFUNDED), `PaymentMethod` (enum: CARD, BANK_TRANSFER, OTHER) |
| Domain Services | `ReservationGateway`, `PaymentGateway` |
| DTOs | `ReservationInfo`, `PaymentGatewayResult` |

Invoice state machine:

```
DRAFT ──> ISSUED ──> PAID ──> REFUNDED
  │          │
  └──> VOID <┘
```

Payment state machine:

```
PENDING ──> SUCCEEDED
   │
   └──> FAILED
   └──> REFUNDED
```

---

## Context Map

```
                                      UserApi (read)             StayApi
┌──────────┐     UserApi          ┌──────────┐              ┌─────────────┐
│   IAM    │ ──────────────────>  │   Stay   │ ◄─────────── │   Billing   │
│          │  (exposes UserApi    │          │  ReservationGateway         │
│ (actors, │   for cross-BC      │ (stays,  │  (reads reservation data)  │
│  users,  │   read access)      │  reserv- │                            │
│  auth)   │                     │  ations) │ ─── integration events ──> │
└──────────┘                     └──────────┘                └────────────┘
     │                                │
     │  Internal domain events        │  GuestGateway
     │  (UserCreated → provision)     │  (reads user data via IAM's UserApi)
     └────────────────────────────────┘
```

**Relationships:**

| Upstream | Downstream | Pattern | Purpose |
|---|---|---|---|
| IAM (UserApi) | Stay | **Anti-Corruption Layer** (read-only via `UserApi`) | Stay reads user data (name, email, VIP status) via `GuestGateway` |
| IAM (internal) | IAM (internal) | **Domain Events** | `UserCreated` → `OnUserCreated` → `ProvisionActorAccountHandler` creates Account + Actor (synchronous, same DB transaction via `TransactionManager`) |
| Stay (StayApi) | Billing | **Anti-Corruption Layer** (via `ReservationGateway`) | Billing reads reservation and stay data for invoice creation |
| Stay | Billing | **Integration Events** | Stay emits events (confirmed, checked out, cancelled) that Billing listens to |
| IAM | All BCs | **Sanctum middleware** | `auth:sanctum` protects Stay and Billing routes |

No BC calls another BC's repository directly. All cross-boundary data flows through Gateway adapters, Integration APIs, and integration events.

---

## Module Structure

Each BC follows the same layered layout:

```
modules/{BC}/
├── Domain/                     # Pure domain — no framework dependencies
│   ├── {Aggregate}.php         # Aggregate root with private constructor + static factory
│   ├── {Aggregate}Id.php       # Identity value object (UUID v7)
│   ├── Entity/                 # Child entities (e.g. SpecialRequest, LineItem, Payment)
│   ├── ValueObject/            # Enums and value objects
│   ├── Event/                  # Domain events (implements DomainEvent)
│   ├── Exception/              # Domain exceptions
│   ├── Repository/             # Repository interface (port)
│   ├── Service/                # Domain service interfaces (ports)
│   ├── Specification/          # Domain specifications / business rules
│   └── Dto/                    # Read-only DTOs for cross-BC data
│
├── Application/                # Use cases — orchestrates domain
│   ├── Command/                # Command DTOs + Handlers
│   ├── Listeners/              # Domain event listeners (transform → integration events)
│   └── Query/                  # Query DTOs + Handlers (if any)
│
├── Infrastructure/             # Framework adapters
│   ├── Persistence/            # Repository implementations, Reflectors, Migrations, Eloquent models, Seeders
│   ├── Http/                   # Inertia view classes, HTTP actions, presenters
│   ├── Routes/                 # API and web route definitions
│   ├── Services/               # Framework service implementations (e.g. BcryptPasswordHasher)
│   ├── Integration/            # Anti-corruption layer adapters and exposed APIs for other BCs
│   ├── IntegrationEvent/       # Integration event classes
│   ├── Messaging/              # Event publishers
│   ├── Stripe/                 # Stripe webhook controller, payment gateway (Billing only)
│   ├── Config/                 # Module-specific configuration
│   └── Providers/              # Service provider (DI bindings, event wiring, migrations, routes)
│
└── Presentation/               # PSR-7 API actions, presenters (some BCs)
    └── Http/
        ├── Action/             # PSR-7 request handlers
        └── Presenter/          # Response presenters
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
| `TransactionManager` | Interface. Single method: `run(callable $callback): mixed`. Wraps use cases in a DB transaction (dependency inversion). |
| `IntegrationEvent` | Interface. Methods: `occurredAt(): DateTimeImmutable`, `toArray(): array`. |
| `EventStore` | Interface for persisting domain events. |
| `StoredEvent` | DTO representing a persisted domain event. |

### Infrastructure Layer

| Class | Purpose |
|---|---|
| `LaravelEventDispatcher` | Implements `EventDispatcher` by delegating to Laravel's `Illuminate\Contracts\Events\Dispatcher`. |
| `LaravelTransactionManager` | Implements `TransactionManager` by delegating to `DB::transaction()`. |
| `EventStoreRecorder` | Records domain events to the `stored_events` table. |
| `TenantContext` | Singleton holding the current tenant (account) ID for multi-tenant scoping. |
| `BelongsToTenant` | Eloquent global scope that filters queries by `account_id`. |
| `HandleInertiaRequests` | Inertia middleware sharing auth/user data with all pages. |
| `EnsureActorType` | Middleware that validates the authenticated actor has the required type(s). |
| `EnsureActorIsOwner` | Middleware that validates actor is an owner type. |
| `EnsureActorIsGuest` | Middleware that validates actor is a guest type. |
| `SetTenantContext` | Middleware that sets the tenant context from the authenticated actor's account. |
| `MapRouteParameters` | Middleware for mapping route parameters. |
| `AuthenticatedUserResolver` | Service resolving the current user's UUID and type from the authenticated actor. |

### Portal Views

The Shared module also hosts the guest-facing portal views (`Infrastructure/Http/View/Portal/`), including dashboard, stay browsing, reservation management, profile, invoice viewing, and payment initiation. The portal routes are defined in `Infrastructure/Routes/portal.php`.

---

## Domain Events

Domain events are recorded inside aggregates via `recordEvent()` and pulled by application-layer handlers after persistence.

### Stay Events

| Event | Recorded When | Payload |
|---|---|---|
| `StayCreated` | `Stay::create()` | `stayId`, `name` |
| `ReservationCreated` | `Reservation::create()` | `reservationId` |
| `ReservationConfirmed` | `confirm()` | `reservationId` |
| `ReservationCancelled` | `cancel()` | `reservationId`, `reason` |
| `GuestCheckedIn` | `checkIn()` | `reservationId` |
| `GuestCheckedOut` | `checkOut()` | `reservationId` |
| `SpecialRequestAdded` | `addSpecialRequest()` | `reservationId`, `requestId` |
| `SpecialRequestFulfilled` | `fulfillSpecialRequest()` | `reservationId`, `requestId` |

### Billing Events

| Event | Recorded When | Payload |
|---|---|---|
| `InvoiceCreated` | `Invoice::createForReservation()` | `invoiceId`, `reservationId` |
| `InvoiceIssued` | `issue()` | `invoiceId` |
| `InvoiceFullyPaid` | `markPaymentSucceeded()` (when total covered) | `invoiceId`, `reservationId` |
| `InvoiceVoided` | `void()` | `invoiceId`, `reason` |
| `InvoiceRefunded` | `refund()` | `invoiceId` |
| `PaymentRecorded` | `recordPayment()` | `invoiceId`, `paymentId` |

### IAM Events

| Event | Recorded When | Payload |
|---|---|---|
| `UserCreated` | `User::create()` | `userId`, `name`, `email`, `hashedPassword`, `actorType`, `accountName?`, `accountSlug?` |
| `ActorRegistered` | `Actor::register()` | `actorId` |
| `AccountCreated` | `Account::create()` | `accountId` |
| `UserContactInfoUpdated` | `updateContactInfo()` | `userId` |
| `UserLoyaltyTierChanged` | `changeLoyaltyTier()` | `userId`, `tier` |

---

## Integration Events

Integration events are enriched, serializable versions of domain events meant for cross-BC or external consumption. They carry all the context needed by consumers (no further lookups required).

### Stay Integration Events

| Integration Event | Source Domain Event | Extra Data |
|---|---|---|
| `ReservationConfirmedEvent` | `ReservationConfirmed` | guestEmail, stayId, checkIn, checkOut, isVip |
| `ReservationCancelledEvent` | `ReservationCancelled` | stayId, checkIn, checkOut, reason |
| `GuestCheckedInEvent` | `GuestCheckedIn` | guestEmail, isVip |
| `GuestCheckedOutEvent` | `GuestCheckedOut` | guestEmail |

All integration events implement `IntegrationEvent` (with `occurredAt()` and `toArray()`).

### Consumer: Billing BC

The Billing BC listens to Stay integration events via thin listeners that delegate to command handlers:

| Integration Event | Thin Listener | Handler |
|---|---|---|
| `ReservationConfirmedEvent` | `OnReservationConfirmed` | Creates a draft invoice for the reservation |
| `GuestCheckedOutEvent` | `OnGuestCheckedOut` | `IssueInvoiceOnCheckoutHandler` — triggers post-checkout billing logic |
| `ReservationCancelledEvent` | `OnReservationCancelled` | `CancelReservationBillingHandler` — voids/refunds the associated invoice |

---

## Event Flow

The full lifecycle of an event from aggregate to integration to cross-BC consumption:

```
1. Aggregate factory/behavior method
   │  $this->recordEvent(new ReservationConfirmed($this->uuid))
   ▼
2. Command Handler (extends EventDispatchingHandler)
   │  $reservation->confirm();
   │  $this->repository->save($reservation);
   │  $this->dispatchEvents($reservation);  // pulls & dispatches all recorded events
   ▼
3. LaravelEventDispatcher
   │  Delegates to Laravel's event system
   ▼
4. Thin Listener (Infrastructure layer)
   │  OnReservationConfirmed::handle(ReservationConfirmed $event)
   │  - Maps event → command → calls handler (NO business logic in listener)
   │  e.g. ProcessNewReservationHandler, ConfirmPaidReservationHandler
   ▼
5. Integration event is dispatched via Laravel's event system
   │
   ├──> IntegrationEventPublisher (logs the event)
   │
   └──> Billing Thin Listener (cross-BC consumer)
        │  OnReservationConfirmed::handle(ReservationConfirmedEvent $event)
        │  - Maps event → command → calls handler
        ▼
6. Handler creates Invoice aggregate, records InvoiceCreated domain event
```

### Thin Listeners Pattern

All event listeners follow a strict pattern: **map event → command → handler call**. No business logic lives in listeners. This keeps listeners as simple routing adapters between the event system and application-layer command handlers.

```php
// Example: OnUserCreated (IAM)
final readonly class OnUserCreated
{
    public function __construct(private ProvisionActorAccountHandler $handler) {}

    public function handle(UserCreated $event): void
    {
        $this->handler->handle(new ProvisionActorAccount(
            userId: (string) $event->userId,
            name: $event->name,
            // ... map event fields to command
        ));
    }
}
```

### TransactionManager

Command handlers that need atomicity (e.g., `RegisterUserHandler`) wrap their logic in `$this->transaction->run(...)`. Since domain event listeners (like `OnUserCreated` → `ProvisionActorAccountHandler`) are synchronous, all operations within a transaction boundary execute in the same DB transaction. This ensures that User creation + Account creation + Actor creation either all succeed or all roll back.

**Event wiring** is done in each BC's service provider. In `IAMServiceProvider::boot()`:

```php
Event::listen(UserCreated::class, OnUserCreated::class);
```

In `StayServiceProvider::boot()`:

```php
Event::listen(ReservationCreated::class, OnReservationCreated::class);
Event::listen(ReservationConfirmed::class, OnReservationConfirmed::class);
Event::listen(ReservationCancelled::class, OnReservationCancelled::class);
Event::listen(GuestCheckedIn::class, OnGuestCheckedIn::class);
Event::listen(GuestCheckedOut::class, OnGuestCheckedOut::class);
```

And in `BillingServiceProvider::boot()`:

```php
Event::listen(ReservationConfirmedEvent::class, OnReservationConfirmed::class);
Event::listen(GuestCheckedOutEvent::class, OnGuestCheckedOut::class);
Event::listen(ReservationCancelledEvent::class, OnReservationCancelled::class);
```

---

## Inter-BC Communication

### GuestGateway (Stay → IAM)

The Stay BC needs user data (name, email, VIP status) but does not depend on the IAM domain model. Instead:

1. **Port** — `Stay/Domain/Service/GuestGateway` interface defines `findByUuid(string): ?GuestInfo`
2. **DTO** — `GuestInfo` is a read-only DTO owned by the Stay BC
3. **Adapter** — `Stay/Infrastructure/Integration/GuestGatewayAdapter` delegates to IAM's `UserApi` and maps to `GuestInfo`

This is an **Anti-Corruption Layer**: the Stay BC translates IAM user data into its own language (`isVip` is derived from `loyalty_tier`).

**Used by:**
- `OnReservationConfirmed` — enriches integration event with guest email and VIP status
- `OnGuestCheckedIn` / `OnGuestCheckedOut` — same enrichment

### UserApi (IAM Integration API)

IAM exposes a `UserApi` (in `Infrastructure/Integration/`) for other BCs to consume. It provides:

- `findByUuid(string): ?UserData` — returns a `UserData` DTO with profile fields

This API is the single entry point for cross-BC read access to user data. The Stay BC's `GuestGatewayAdapter` depends on it.

> **Note:** User creation during registration is handled internally by the `ProvisionActorAccountHandler` (triggered by the `UserCreated` domain event), not via an external gateway.

### StayApi (Stay Integration API)

The Stay BC exposes a `StayApi` (in `Infrastructure/Integration/`) for other BCs to query stay data:

- `findByUuid(string): ?StayData` — returns stay details (name, slug, type, category, price, capacity, status)
- `isAvailable(string): bool` — checks if a stay is active

### ReservationGateway (Billing → Stay)

The Billing BC needs reservation and stay data (guest ID, stay name, price per night, check-in/check-out) for invoice creation:

1. **Port** — `Billing/Domain/Service/ReservationGateway` interface defines `findReservation(string): ?ReservationInfo`
2. **DTO** — `ReservationInfo` is a read-only DTO owned by the Billing BC with fields: `reservationId`, `guestId`, `stayId`, `stayName`, `accountId`, `checkIn`, `checkOut`, `nights`, `pricePerNight`
3. **Adapter** — `Billing/Infrastructure/Integration/ReservationGatewayAdapter` queries the Stay BC's Eloquent models and maps to `ReservationInfo`

### PaymentGateway (Billing → Stripe)

The Billing BC integrates with Stripe for payment processing:

1. **Port** — `Billing/Domain/Service/PaymentGateway` interface
2. **Adapter** — `Billing/Infrastructure/Stripe/StripePaymentGateway` handles Stripe API calls
3. **Webhook** — `Billing/Infrastructure/Stripe/StripeWebhookController` processes payment success/failure callbacks

### No Direct Coupling

- No BC imports another BC's domain classes
- No BC calls another BC's repository (except Billing's `ReservationGatewayAdapter` which uses Stay Eloquent models for pragmatic read access)
- Cross-BC data flows through `UserApi`, `StayApi`, Gateway adapters, and integration events
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
| `StayReflector` | `Stay` | `EloquentStayRepository` |
| `ReservationReflector` | `Reservation` (with nested `SpecialRequest[]`) | `EloquentReservationRepository` |
| `SpecialRequestReflector` | `SpecialRequest` | `EloquentReservationRepository` (during deserialization) |
| `InvoiceReflector` | `Invoice` (with nested `LineItem[]` and `Payment[]`) | `EloquentInvoiceRepository` |
| `UserReflector` | `User` | `EloquentUserRepository` |
| `ActorReflector` | `Actor` | `EloquentActorRepository` |
| `AccountReflector` | `Account` | `EloquentAccountRepository` |

Reflectors are **unaffected by private constructors** — they use `ReflectionClass::newInstanceWithoutConstructor()`.

### Event Store

Domain events are persisted to the `stored_events` table via `EventStoreRecorder`. The `EventSerializer` handles serialization/deserialization. This provides an audit trail and enables future event replay capabilities.

---

## IAM Deep Dive — Actors, Accounts, Users & Authentication

### Multi-Tenancy: Accounts

An **Account** is the IAM aggregate that represents a tenant. Each account is a property owner or organization. All actors belong to an account, and all main tables across BCs (stays, reservations, invoices) carry an `account_id` foreign key for data isolation.

### Users

The **User** aggregate lives within the IAM BC. It manages user profiles — name, email, phone, document, loyalty tier. Guests and owners share a single `users` table; owners have a `null` loyalty tier. User profiles are created during actor registration and managed via CRUD operations in the owner dashboard.

The `UserApi` exposes user data to other BCs (Stay uses it via `GuestGateway` to enrich integration events with guest email and VIP status).

### Types

**Type** is a domain entity stored in the `types` table. Types are seeded (`superadmin`, `owner`, `guest`) and referenced by actors via the `actor_type_pivot` table (many-to-many). The `TypeName` enum provides type-safe domain logic:

| `TypeName` | Purpose |
|---|---|
| `SUPERADMIN` | System administrator. Can impersonate any owner to manage their properties. |
| `OWNER` | Property owner / manager. Manages stays, reservations, invoices, and guests. |
| `GUEST` | Guest who accesses the portal for stay browsing, reservations, and profile management. |

### What is an Actor?

An **Actor** is the IAM aggregate — it represents any identity that can authenticate against the system. Actors have types (not roles) that control access levels.

An Actor belongs to an Account (tenant) and has one or more Types. Each actor has a `userId` linking to a `User` in the same IAM module.

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
        public readonly ?int $userId,            // FK to users table
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
- **`userId` FK** — direct foreign key to `users` table
- **Many-to-many types** — actors can have multiple types via the `actor_type_pivot` table

### Domain Service Ports

The Actor aggregate never touches framework code. Domain service interfaces define the ports:

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

**`EmailUniquenessChecker`** — validates email uniqueness for actors during registration:
Implemented by `EloquentEmailUniquenessChecker`.

**`UserEmailUniquenessChecker`** — validates email uniqueness at the User aggregate level:
```php
interface UserEmailUniquenessChecker
{
    public function isEmailTaken(string $email): bool;
}
```
Implemented by `EloquentUserEmailUniquenessChecker`. Enforced as a domain invariant inside `User::create()` — throws `UserAlreadyExistsException` if email is taken.

### The Dual-Model Approach

IAM has two representations of an actor:

| | `Actor` (Domain) | `ActorModel` (Infrastructure) |
|---|---|---|
| Layer | Domain | Infrastructure (Eloquent) |
| Purpose | Business logic, invariants | Sanctum token issuance, Laravel auth middleware |
| Created by | `Actor::register()` | Seeded or created alongside the domain actor |
| Persistence | `EloquentActorRepository` writes to `actors` table | Reads from the same `actors` table |

Both read/write the same `actors` table. The domain `Actor` is persisted via `EloquentActorRepository` (through `ActorReflector` for hydration). The `ActorModel` is only used by `SanctumTokenManager`, Laravel's `auth:sanctum` middleware, and the web login/register views. It has `BelongsToMany` relationship to `ActorTypeModel` via the `actor_type_pivot` table, and a `BelongsTo` relationship to `AccountModel`.

### Token Expiration

Sanctum tokens are configured to expire after 24 hours (configurable via `SANCTUM_TOKEN_EXPIRATION` env var). Web sessions use standard Laravel session expiration. Expired Inertia sessions (419 CSRF errors) are handled by redirecting to `/login` both server-side (via exception handler) and client-side (via Vue global error handler).

### Use Cases (Command Handlers)

**`RegisterUserHandler`** — Guest Registration:
```
1. Wrap in TransactionManager
2. Generate new UserId
3. Hash password via PasswordHasher → HashedPassword
4. User::create(...) with UserEmailUniquenessChecker invariant
   → records UserCreated event (with hashedPassword, actorType=guest, accountName, accountSlug)
5. Save User to repository
6. dispatchEvents(user) → triggers OnUserCreated listener
   → ProvisionActorAccountHandler creates Account + Actor (same transaction)
7. Return UserId
```

**`RegisterHotelOwnerHandler`** — Owner Registration:
```
1. Same flow as RegisterUserHandler
2. actorType=owner, loyaltyTier=null
3. ProvisionActorAccountHandler creates Account + Actor for owner
```

**`ProvisionActorAccountHandler`** — Actor & Account Provisioning (triggered by UserCreated):
```
1. Create Account aggregate
2. Resolve user numeric ID from repository
3. Look up the actor Type by name
4. Generate new ActorId
5. Actor::register(...) → creates the aggregate with account, type, userId
6. Save Account and Actor to repositories
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
POST /auth/register      │    Token      │  GET  /users/*               │
  → RegisterUserHandler  │ ──────────>   │  POST /stays/*               │
          │              │  Bearer       │  POST /reservations/*        │
POST /auth/login         │  header       │  GET  /invoices/*            │
  → AuthenticateActorHandler             │  POST /auth/logout           │
          │              │               │  ...all other endpoints      │
          └──────────────┘               └──────────────────────────────┘
```

1. **Register** — `POST /api/auth/register` with `{ name, email, password, phone, document }`. Creates a User (which triggers `UserCreated` → `ProvisionActorAccountHandler` to create Account + Actor), returns the actor resource. Also available as web form at `/register`. Validates: email format, password min 8 chars, phone digits only. Rejects duplicate emails via `UserEmailUniquenessChecker` domain invariant.

2. **Login** — `POST /api/auth/login` with `{ email, password }`. Verifies credentials against the domain aggregate, then issues a Sanctum token via `TokenManager`. Returns `{ token, actor_id }`.

3. **Authenticated requests** — include `Authorization: Bearer {token}`. Laravel's `auth:sanctum` middleware validates the token against the `ActorModel` (Eloquent). All Stay, Billing, and User routes are protected this way.

4. **Logout** — `POST /api/auth/logout` (authenticated). Revokes all tokens for the actor.

### Middleware

| Middleware | Purpose |
|---|---|
| `HandleInertiaRequests` | Shares auth data (actor, user, types) with all Inertia pages. |
| `EnsureActorType` | Validates actor has required type(s). Used as `type:owner,superadmin` in route groups. |
| `EnsureActorIsOwner` | Validates actor is an owner type. |
| `EnsureActorIsGuest` | Validates actor is a guest type. |
| `SetTenantContext` | Sets `TenantContext` from the authenticated actor's account. |
| `MapRouteParameters` | Maps route parameters for controller resolution. |

### Exceptions

| Exception | Thrown When |
|---|---|
| `ActorAlreadyExistsException` | Registration with an email that's already taken |
| `ActorNotFoundException` | Login with an unknown email |
| `InvalidCredentialsException` | Login with wrong password |
