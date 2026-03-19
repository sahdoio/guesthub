# Hotel Management System — Domain Model Explanation

## Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                            MODULAR MONOLITH                                              │
│                                                                                          │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────────────┐   │
│  │   IAM CONTEXT        │  │   USER CONTEXT        │  │   RESERVATION CONTEXT        │   │
│  │                      │  │                       │  │                              │   │
│  │   Domain/            │  │   Domain/             │  │   Domain/                    │   │
│  │   ├── Actor (AR)     │  │   ├── User (AR)       │  │   ├── Reservation (AR)       │   │
│  │   ├── Account (AR)   │  │   └── LoyaltyTier     │  │   │   ├── Period (VO)        │   │
│  │   ├── Hotel (AR)     │  │       (VO, nullable)  │  │   │   └── SpecialRequest (E) │   │
│  │   ├── Type (E)       │  │                       │  │   ├── Domain Events          │   │
│  │   └── HashedPassword │  │   Domain events:      │  │   └── Repository Interfaces  │   │
│  │       (VO)           │  │   UserCreated,        │  │                              │   │
│  │                      │  │   UserContactInfo-    │  │                              │   │
│  │                      │  │   Updated,            │  │                              │   │
│  │                      │  │   UserLoyaltyTier-    │  │                              │   │
│  │                      │  │   Changed             │  │                              │   │
│  └──────────┬───────────┘  └───────────▲───────────┘  └──────────────┬───────────────┘   │
│             │                          │                             │                    │
│             │  UserGateway             │  GuestGateway               │                   │
│             │  (creates profiles)      │  (reads profiles)          │                   │
│             └──────────────────────────┘◄────────────────────────────┘                   │
│                                        │                                                 │
│                                    UserApi                                               │
│                                  (single entry point                                     │
│                                   for cross-BC access)                                   │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐    │
│  │   SHARED KERNEL: AggregateRoot, Entity, ValueObject, Identity, DomainEvent,     │    │
│  │                  EventDispatcher, EventDispatchingHandler, IntegrationEvent      │    │
│  └──────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                          │
│  Integration events are currently dispatched via Laravel's event system and logged.      │
│  A message broker is planned for future async cross-BC communication.                    │
│                                                                                          │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## Entity vs Value Object — When to Use

| Aspect | Entity | Value Object |
|--------|--------|--------------|
| **Identity** | Has unique ID | No identity (compared by value) |
| **Mutability** | Can change state | Immutable |
| **Lifecycle** | Tracked over time | Replaceable |
| **Example** | SpecialRequest (can be fulfilled) | ReservationPeriod (replace entirely to update) |

---

# BC1: RESERVATION CONTEXT

## Aggregate: Reservation

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              RESERVATION AGGREGATE                                      │
│                                                                                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐ │
│  │                      Reservation (Aggregate Root)                                  │ │
│  │                                                                                    │ │
│  │  Identity:                                                                         │ │
│  │  • id: ReservationId                                                               │ │
│  │                                                                                    │ │
│  │  State:                                                                            │ │
│  │  • guestId: string                        (soft link to User BC)                   │ │
│  │  • status: ReservationStatus                                                       │ │
│  │  • roomType: string                                                                │ │
│  │  • assignedRoomNumber: ?string                                                     │ │
│  │  • createdAt, confirmedAt, checkedInAt, checkedOutAt, cancelledAt                  │ │
│  │  • cancellationReason: ?string                                                     │ │
│  │                                                                                    │ │
│  │  Compositions:                                                                     │ │
│  │  • period: ReservationPeriod (VO)                                                  │ │
│  │  • specialRequests: SpecialRequest[] (Entities)  ◄── ENTITIES with own identity   │ │
│  │                                                                                    │ │
│  │  Behavior:                                                                         │ │
│  │  • confirm(): void                                                                 │ │
│  │  • checkIn(roomNumber): void                                                       │ │
│  │  • checkOut(): void                                                                │ │
│  │  • cancel(reason): void                                                            │ │
│  │  • addSpecialRequest(type, description): SpecialRequestId                          │ │
│  │  • fulfillSpecialRequest(requestId): void                                          │ │
│  │  • removeSpecialRequest(requestId): void                                           │ │
│  │                                                                                    │ │
│  └───────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                         │
│         │ contains                                    │ contains                        │
│         ▼                                             ▼                                 │
│                                                                                         │
│  ┌─────────────────────────┐    ┌──────────────────────────────┐                       │
│  │   ReservationPeriod     │    │     SpecialRequest           │                       │
│  │    (Value Object)       │    │       (Entity)               │                       │
│  │                         │    │                              │                       │
│  │ • checkIn: Date         │    │ • id: SpecialRequestId  ◄─── │                       │
│  │ • checkOut: Date        │    │ • type: RequestType     own  │                       │
│  │                         │    │ • description: string   ID   │                       │
│  │ Derived:                │    │ • status: RequestStatus      │                       │
│  │ • nights(): int         │    │ • fulfilledAt: ?DateTime     │                       │
│  │ • overlaps(): bool      │    │ • createdAt: DateTime        │                       │
│  │ • contains(): bool      │    │                              │                       │
│  │                         │    │ Mutable:                     │                       │
│  │ Immutable               │    │ • fulfill(): void            │                       │
│  │                         │    │ • cancel(): void             │                       │
│  └─────────────────────────┘    │ • changeDescription(): void  │                       │
│                                 │                              │                       │
│                                 │ Lifecycle tied to            │                       │
│                                 │ Reservation (cannot exist    │                       │
│                                 │ independently)               │                       │
│                                 └──────────────────────────────┘                       │
└─────────────────────────────────────────────────────────────────────────────────────────┘

Guest data (name, email, VIP status) is NOT stored in the aggregate. Instead, it's fetched
on-demand via the GuestGateway port, which returns a GuestInfo DTO. This keeps the aggregate
decoupled from the User BC.
```

### Why SpecialRequest is an ENTITY (not VO):

1. **Has Identity** — Each request has a `SpecialRequestId`, allowing you to reference it: "fulfill request X"
2. **Changes State** — Request can be fulfilled, cancelled, or description updated
3. **Tracked Over Time** — You need to know when it was created, when fulfilled
4. **Individual Operations** — You can remove or fulfill a specific request by ID

---

## Domain Layer Structure (BC1)

```
Reservation/
└── Domain/
    ├── Reservation.php                    # Aggregate Root (Entity)
    ├── ReservationId.php                  # Identity Value Object
    │
    ├── Entity/
    │   └── SpecialRequest.php             # Child Entity (has identity, mutable)
    │
    ├── ValueObject/
    │   ├── ReservationPeriod.php          # VO
    │   ├── SpecialRequestId.php           # Identity VO for child entity
    │   ├── ReservationStatus.php          # Enum as VO
    │   ├── RequestType.php                # Enum
    │   └── RequestStatus.php              # Enum (pending, fulfilled, cancelled)
    │
    ├── Dto/                               # Read-only DTOs for cross-BC data
    │   ├── GuestInfo.php                  # Guest data fetched via GuestGateway
    │   ├── RoomAvailability.php           # Room availability from InventoryGateway
    │   └── RoomTypeInfo.php               # Room type details from InventoryGateway
    │
    ├── Event/                             # DOMAIN EVENTS (internal to BC)
    │   ├── ReservationCreated.php
    │   ├── ReservationConfirmed.php
    │   ├── GuestCheckedIn.php
    │   ├── GuestCheckedOut.php
    │   ├── ReservationCancelled.php
    │   ├── SpecialRequestAdded.php
    │   └── SpecialRequestFulfilled.php
    │
    ├── Repository/
    │   └── ReservationRepository.php      # Interface only
    │
    ├── Service/                           # Domain Service Interfaces (ports)
    │   ├── GuestGateway.php               # Port for fetching user data from User BC
    │   └── InventoryGateway.php           # Port for checking room availability
    │
    ├── Policies/                          # Domain Policies
    │   └── ReservationPolicy.php          # Business rules
    │
    └── Exception/
        ├── ReservationNotFoundException.php
        ├── InvalidReservationStateException.php
        └── MaxSpecialRequestsExceededException.php
```

---

## Domain Events

Domain events are recorded inside the Reservation aggregate via `recordEvent()` and dispatched by command handlers after persistence. Only the Reservation BC emits domain events currently (the User BC records events but has no listeners yet).

| Event | Trigger | Payload |
|-------|---------|---------|
| `ReservationCreated` | `Reservation::create()` | `reservationId` |
| `ReservationConfirmed` | `confirm()` | `reservationId` |
| `ReservationCancelled` | `cancel()` | `reservationId`, `reason` |
| `GuestCheckedIn` | `checkIn()` | `reservationId`, `roomNumber` |
| `GuestCheckedOut` | `checkOut()` | `reservationId` |
| `SpecialRequestAdded` | `addSpecialRequest()` | `reservationId`, `requestId` |
| `SpecialRequestFulfilled` | `fulfillSpecialRequest()` | `reservationId`, `requestId` |

---

## Integration Events

Integration events are enriched, serializable versions of domain events for cross-BC or external consumption. They carry all context needed by consumers (no further lookups required). All implement `IntegrationEvent` (with `occurredAt()` and `toArray()`).

| Event | Source Domain Event | Extra Data |
|-------|-------------------|------------|
| `ReservationConfirmedEvent` | `ReservationConfirmed` | guestEmail, roomType, checkIn, checkOut, isVip |
| `ReservationCancelledEvent` | `ReservationCancelled` | roomType, checkIn, checkOut, reason |
| `GuestCheckedInEvent` | `GuestCheckedIn` | roomNumber, guestEmail, isVip |
| `GuestCheckedOutEvent` | `GuestCheckedOut` | roomNumber, guestEmail |

Currently, integration events are dispatched via Laravel's event system and logged by `IntegrationEventPublisher`. No consumer BCs exist yet — a message broker is planned for future async delivery.

### Domain Event vs Integration Event

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                         │
│   DOMAIN EVENTS                              INTEGRATION EVENTS                         │
│   (Internal to BC)                           (Cross-BC communication)                   │
│                                                                                         │
│   ┌─────────────────────────────┐            ┌─────────────────────────────┐           │
│   │ ReservationConfirmed        │            │ ReservationConfirmedEvent   │           │
│   │                             │            │                             │           │
│   │ • Used within Reservation   │   ───►     │ • Dispatched via Laravel    │           │
│   │   BC for side effects       │  mapped    │   event system              │           │
│   │ • Triggers internal         │    to      │ • Logged by publisher       │           │
│   │   handlers                  │            │ • Contains only data other  │           │
│   │ • Rich domain object refs   │            │   BCs need (no domain refs) │           │
│   └─────────────────────────────┘            └─────────────────────────────┘           │
│                                                                                         │
│   WHERE THEY LIVE:                           WHERE THEY LIVE:                          │
│   Domain/Event/                              Infrastructure/IntegrationEvent/           │
│                                                                                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

### Flow: Domain Event → Integration Event

```
┌──────────────┐     ┌──────────────────┐     ┌─────────────────────┐     ┌──────────────────┐
│              │     │                  │     │                     │     │                  │
│ Reservation  │────►│  Domain Event    │────►│  Application Layer  │────►│  Integration     │
│ .confirm()   │     │  Raised          │     │  Listener maps to   │     │  Event Dispatched│
│              │     │                  │     │  Integration Event  │     │  (via Laravel)   │
└──────────────┘     └──────────────────┘     └─────────────────────┘     └──────────────────┘
                                                                                  │
                                                                                  ▼
                                                                    ┌──────────────────────┐
                                                                    │ IntegrationEvent     │
                                                                    │ Publisher             │
                                                                    │ (logs the event)     │
                                                                    └──────────────────────┘
```

> **Future:** When consumer BCs exist (e.g., Notifications), a message broker will replace the current log-only publisher to enable async cross-BC event delivery.

---

# CROSS-BC INTEGRATION

The system has three active integration paths, all using the Gateway + Adapter (ACL) pattern:

| From | To | Gateway (Port) | Adapter (ACL) | Mechanism |
|------|----|----------------|---------------|-----------|
| Reservation | User | `GuestGateway` | `GuestGatewayAdapter` → `UserApi` | Sync (direct call) |
| Reservation | Inventory | `InventoryGateway` | `InventoryGatewayAdapter` → `InventoryApi` | Sync (direct call) |
| IAM | User | `UserGateway` | `UserGatewayAdapter` → `UserApi` | Sync (direct call) |

## Reservation → User BC

Reservation needs user data (name, email, VIP status) to enrich integration events. The domain defines a port; the infrastructure adapter calls User BC's exposed API.

```php
// Reservation/Domain/Service/GuestGateway.php (PORT)
interface GuestGateway
{
    public function findByUuid(string $guestId): ?GuestInfo;
}

// Reservation/Domain/Dto/GuestInfo.php
readonly class GuestInfo
{
    public function __construct(
        public string $guestId,
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public bool $isVip,
    ) {}
}

// Reservation/Infrastructure/Integration/GuestGatewayAdapter.php (ACL)
// Calls UserApi and translates to Reservation's own DTO
class GuestGatewayAdapter implements GuestGateway
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {}

    public function findByUuid(string $guestId): ?GuestInfo
    {
        $data = $this->userApi->findByUuid($guestId);
        if ($data === null) return null;

        $isVip = in_array($data->loyaltyTier, ['gold', 'platinum'], true);

        return new GuestInfo(
            guestId: $data->uuid,
            fullName: $data->fullName,
            email: $data->email,
            phone: $data->phone,
            document: $data->document,
            isVip: $isVip,       // ACL: translates loyalty tier to boolean
        );
    }
}
```

## Reservation → Inventory BC

Same Anti-Corruption Layer pattern, backed by the Inventory BC:

```php
// Reservation/Domain/Service/InventoryGateway.php (PORT)
interface InventoryGateway
{
    public function checkAvailability(string $roomType, ReservationPeriod $period): RoomAvailability;
    public function getRoomTypeInfo(string $roomType): RoomTypeInfo;
    public function listAvailableRooms(string $roomType): array;
    public function isRoomAvailable(string $roomNumber): bool;
}
```

## IAM → User BC

When a new actor registers, IAM creates a user profile via the gateway. This is how the User BC gets populated for guests. Owners also get user profiles (with null loyalty tier).

```php
// IAM/Domain/Service/UserGateway.php (PORT)
interface UserGateway
{
    public function create(string $name, string $email, string $phone, string $document, ?string $loyaltyTier = null): int;
}

// IAM/Infrastructure/Integration/UserGatewayAdapter.php (ACL)
class UserGatewayAdapter implements UserGateway
{
    public function __construct(
        private UserApi $userApi,
    ) {}

    public function create(string $name, string $email, string $phone, string $document, ?string $loyaltyTier = null): int
    {
        return $this->userApi->create(
            name: $name, email: $email, phone: $phone, document: $document, loyaltyTier: $loyaltyTier,
        );
    }
}
```

## User BC — Exposed Integration API

The User BC exposes `UserApi` as an internal API for other BCs. It is **not** an HTTP endpoint — it's a PHP class resolved via the service container. Returns DTOs with primitives only.

```php
// User/Infrastructure/Integration/UserApi.php
class UserApi
{
    public function __construct(
        private CreateUserHandler $createHandler,
        private UserRepository $repository,
    ) {}

    public function create(string $name, string $email, string $phone, string $document, ?string $loyaltyTier = null): int;
    public function findByUuid(string $uuid): ?UserData;
    public function findById(int $id): ?UserData;
}

// User/Infrastructure/Integration/Dto/UserData.php
readonly class UserData
{
    public function __construct(
        public string $uuid,
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public ?string $loyaltyTier,
    ) {}
}
```

---

# COMPLETE FOLDER STRUCTURE

```
src/
├── Reservation/                              # BC: Reservation
│   │
│   ├── Domain/
│   │   ├── Reservation.php                   # Aggregate Root
│   │   ├── ReservationId.php                 # Identity VO
│   │   │
│   │   ├── Entity/
│   │   │   └── SpecialRequest.php            # Child Entity
│   │   │
│   │   ├── ValueObject/
│   │   │   ├── ReservationPeriod.php
│   │   │   ├── SpecialRequestId.php
│   │   │   ├── ReservationStatus.php         # Enum
│   │   │   ├── RequestType.php               # Enum
│   │   │   └── RequestStatus.php             # Enum (pending, fulfilled, cancelled)
│   │   │
│   │   ├── Dto/                              # DTOs for cross-BC data
│   │   │   ├── GuestInfo.php
│   │   │   ├── RoomAvailability.php
│   │   │   └── RoomTypeInfo.php
│   │   │
│   │   ├── Event/                            # Domain Events (internal)
│   │   │   ├── ReservationCreated.php
│   │   │   ├── ReservationConfirmed.php
│   │   │   ├── GuestCheckedIn.php
│   │   │   ├── GuestCheckedOut.php
│   │   │   ├── ReservationCancelled.php
│   │   │   ├── SpecialRequestAdded.php
│   │   │   └── SpecialRequestFulfilled.php
│   │   │
│   │   ├── Repository/
│   │   │   └── ReservationRepository.php     # Interface
│   │   │
│   │   ├── Service/                          # Domain Service Interfaces (ports)
│   │   │   ├── GuestGateway.php              # Port for User BC data
│   │   │   └── InventoryGateway.php          # Port for room availability
│   │   │
│   │   ├── Policies/
│   │   │   └── ReservationPolicy.php         # Business rules
│   │   │
│   │   └── Exception/
│   │       ├── ReservationNotFoundException.php
│   │       ├── InvalidReservationStateException.php
│   │       └── MaxSpecialRequestsExceededException.php
│   │
│   ├── Application/
│   │   ├── Command/                          # Commands + Handlers in same dir
│   │   │   ├── CreateReservation.php
│   │   │   ├── CreateReservationHandler.php
│   │   │   ├── ConfirmReservation.php
│   │   │   ├── ConfirmReservationHandler.php
│   │   │   ├── CheckInGuest.php
│   │   │   ├── CheckInGuestHandler.php
│   │   │   ├── CheckOutGuest.php
│   │   │   ├── CheckOutGuestHandler.php
│   │   │   ├── CancelReservation.php
│   │   │   ├── CancelReservationHandler.php
│   │   │   ├── AddSpecialRequest.php
│   │   │   └── AddSpecialRequestHandler.php
│   │   │
│   │   └── Listeners/                        # Domain event → Integration event
│   │       ├── OnReservationConfirmed.php
│   │       ├── OnReservationCancelled.php
│   │       ├── OnGuestCheckedIn.php
│   │       └── OnGuestCheckedOut.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── EloquentReservationRepository.php
│       │   ├── ReservationReflector.php
│       │   ├── SpecialRequestReflector.php
│       │   └── Eloquent/
│       │       └── ReservationModel.php      # Eloquent model (internal)
│       │
│       ├── Integration/                      # ACL adapters
│       │   ├── GuestGatewayAdapter.php       # Implements GuestGateway (calls UserApi)
│       │   └── InventoryGatewayAdapter.php   # Implements InventoryGateway
│       │
│       ├── IntegrationEvent/                 # Integration Events (cross-BC)
│       │   ├── ReservationConfirmedEvent.php
│       │   ├── ReservationCancelledEvent.php
│       │   ├── GuestCheckedInEvent.php
│       │   └── GuestCheckedOutEvent.php
│       │
│       ├── Messaging/
│       │   └── IntegrationEventPublisher.php
│       │
│       ├── Http/
│       │   └── View/                         # Inertia view classes
│       │
│       ├── Presentation/
│       │   └── Http/
│       │       └── Action/                   # PSR-7 API actions
│       │
│       ├── Routes/
│       │   ├── api.php
│       │   └── web.php
│       │
│       └── Providers/
│           └── ReservationServiceProvider.php
│
├── User/                                     # BC: User (merged Guest + Owner)
│   │
│   ├── Domain/
│   │   ├── User.php                          # Aggregate Root
│   │   ├── UserId.php                        # Identity VO
│   │   │
│   │   ├── ValueObject/
│   │   │   └── LoyaltyTier.php               # Enum (bronze, silver, gold, platinum)
│   │   │
│   │   ├── Event/
│   │   │   ├── UserCreated.php
│   │   │   ├── UserContactInfoUpdated.php
│   │   │   └── UserLoyaltyTierChanged.php
│   │   │
│   │   ├── Repository/
│   │   │   └── UserRepository.php            # Interface
│   │   │
│   │   └── Exception/
│   │       └── UserNotFoundException.php
│   │
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateUser.php
│   │   │   ├── CreateUserHandler.php
│   │   │   ├── UpdateUser.php
│   │   │   └── UpdateUserHandler.php
│   │   │
│   │   └── Query/
│   │       ├── ListUsers.php
│   │       ├── ListUsersHandler.php
│   │       ├── GetUserStats.php
│   │       ├── GetUserStatsHandler.php
│   │       └── UserStatsResult.php
│   │
│   ├── Presentation/
│   │   └── Http/
│   │       ├── Action/                       # PSR-7 API actions
│   │       └── Presenter/
│   │           └── UserPresenter.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── UserReflector.php
│       │   ├── Eloquent/
│       │   │   ├── UserModel.php
│       │   │   └── EloquentUserRepository.php
│       │   ├── Migrations/
│       │   └── Seeders/
│       │       └── UserSeeder.php
│       │
│       ├── Integration/                      # API exposed for other BCs
│       │   ├── UserApi.php                   # Entry point for cross-BC access
│       │   └── Dto/
│       │       └── UserData.php              # DTO returned by the API
│       │
│       ├── Http/
│       │   └── View/                         # Inertia view classes
│       │
│       ├── Routes/
│       │   ├── api.php
│       │   └── web.php
│       │
│       └── Providers/
│           └── UserServiceProvider.php
│
├── IAM/                                      # BC: Identity & Access Management
│   │
│   ├── Domain/
│   │   ├── Actor.php                         # Aggregate Root
│   │   ├── ActorId.php                       # Identity VO
│   │   ├── Account.php                       # Aggregate Root
│   │   ├── AccountId.php                     # Identity VO
│   │   ├── Hotel.php                         # Aggregate Root
│   │   ├── Type.php                          # Entity
│   │   ├── TypeId.php                        # Identity VO
│   │   │
│   │   ├── ValueObject/
│   │   │   ├── TypeName.php                  # Enum (SUPERADMIN, OWNER, GUEST)
│   │   │   └── HashedPassword.php            # VO
│   │   │
│   │   ├── Event/
│   │   │   ├── ActorRegistered.php
│   │   │   ├── AccountCreated.php
│   │   │   └── HotelCreated.php
│   │   │
│   │   ├── Repository/
│   │   │   ├── ActorRepository.php           # Interface
│   │   │   ├── AccountRepository.php         # Interface
│   │   │   ├── HotelRepository.php           # Interface
│   │   │   └── TypeRepository.php            # Interface
│   │   │
│   │   ├── Service/
│   │   │   ├── PasswordHasher.php            # Interface
│   │   │   ├── TokenManager.php              # Interface
│   │   │   └── UserGateway.php               # Interface (port for User BC)
│   │   │
│   │   └── Exception/
│   │       ├── ActorAlreadyExistsException.php
│   │       ├── ActorNotFoundException.php
│   │       └── InvalidCredentialsException.php
│   │
│   ├── Application/
│   │   └── Command/
│   │       ├── RegisterActor.php
│   │       ├── RegisterActorHandler.php
│   │       ├── RegisterHotelOwner.php
│   │       ├── RegisterHotelOwnerHandler.php
│   │       ├── AuthenticateActor.php
│   │       ├── AuthenticateActorHandler.php
│   │       ├── RevokeToken.php
│   │       └── RevokeTokenHandler.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── ActorReflector.php
│       │   ├── AccountReflector.php
│       │   └── Eloquent/
│       │       ├── ActorModel.php            # Eloquent model (for Sanctum)
│       │       ├── AccountModel.php
│       │       ├── TypeModel.php
│       │       ├── EloquentActorRepository.php
│       │       ├── EloquentAccountRepository.php
│       │       └── EloquentTypeRepository.php
│       │
│       ├── Integration/
│       │   └── UserGatewayAdapter.php        # Implements UserGateway (calls UserApi)
│       │
│       ├── Services/
│       │   ├── BcryptPasswordHasher.php
│       │   └── SanctumTokenManager.php
│       │
│       ├── Http/
│       │   └── View/                         # Inertia view classes
│       │
│       ├── Presentation/
│       │   └── Http/
│       │       ├── Action/                   # PSR-7 API actions
│       │       └── Presenter/
│       │           └── ActorPresenter.php
│       │
│       ├── Routes/
│       │   ├── api.php
│       │   └── web.php
│       │
│       └── Providers/
│           └── IAMServiceProvider.php
│
├── Inventory/                                # BC: Inventory
│   │  (similar structure)
│
└── Shared/                                   # Shared Kernel
    ├── Domain/
    │   ├── AggregateRoot.php
    │   ├── Entity.php
    │   ├── ValueObject.php
    │   ├── DomainEvent.php
    │   ├── Identity.php
    │   └── PaginatedResult.php
    │
    ├── Application/
    │   ├── EventDispatcher.php
    │   ├── EventDispatchingHandler.php
    │   └── Messaging/
    │       └── IntegrationEvent.php
    │
    └── Infrastructure/
        ├── Persistence/
        │   ├── TenantContext.php
        │   └── BelongsToTenant.php
        ├── Http/
        │   └── Middleware/
        │       ├── EnsureActorType.php
        │       ├── EnsureActorIsOwner.php
        │       ├── EnsureActorIsGuest.php
        │       ├── SetTenantContext.php
        │       └── HandleInertiaRequests.php
        ├── Service/
        │   └── AuthenticatedUserResolver.php
        └── Messaging/
            └── LaravelEventDispatcher.php
```

---

# SUMMARY

| Aspect | Reservation | User | IAM | Inventory |
|--------|------------|------|-----|-----------|
| **Aggregate Root** | Reservation | User | Actor, Account, Hotel | Room |
| **Child Entities** | SpecialRequest | — | Type | — |
| **Value Objects** | ReservationPeriod, ReservationStatus, RequestType, RequestStatus, SpecialRequestId | LoyaltyTier (nullable) | TypeName, HashedPassword | RoomType, RoomStatus |
| **DTOs** | GuestInfo, RoomAvailability, RoomTypeInfo | UserData (integration) | — | RoomData (integration) |
| **Domain Events** | 7 (internal) | 3 | 3 | — |
| **Integration Events** | 4 (published) | — | — | — |
| **Cross-BC Ports** | GuestGateway, InventoryGateway | — | UserGateway | — |
