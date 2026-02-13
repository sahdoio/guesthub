# Hotel Management System — Domain Model Explanation

## Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                            MODULAR MONOLITH                                              │
│                                                                                          │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────────────┐   │
│  │   IAM CONTEXT        │  │   GUEST CONTEXT       │  │   RESERVATION CONTEXT        │   │
│  │                      │  │                       │  │                              │   │
│  │   Domain/            │  │   Domain/             │  │   Domain/                    │   │
│  │   ├── Actor (AR)     │  │   ├── GuestProfile    │  │   ├── Reservation (AR)       │   │
│  │   ├── ActorType      │  │   │   (AR)            │  │   │   ├── Period (VO)        │   │
│  │   └── HashedPassword │  │   └── LoyaltyTier     │  │   │   └── SpecialRequest (E) │   │
│  │                      │  │                       │  │   ├── Domain Events          │   │
│  │   No domain events   │  │   No domain events    │  │   └── Repository Interfaces  │   │
│  │                      │  │                       │  │                              │   │
│  └──────────┬───────────┘  └───────────▲───────────┘  └──────────────┬───────────────┘   │
│             │                          │                             │                    │
│             │  GuestProfileGateway     │  GuestGateway               │                   │
│             │  (creates profiles)      │  (reads profiles)           │                   │
│             └──────────────────────────┘◄────────────────────────────┘                   │
│                                        │                                                 │
│                                  GuestProfileApi                                         │
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
│  │  • guestProfileId: string               (soft link to Guest BC)                    │ │
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
decoupled from the Guest BC.
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
    │   ├── GuestGateway.php               # Port for fetching guest data from Guest BC
    │   └── InventoryGateway.php           # Port for checking room availability
    │
    ├── Policies/                          # Domain Policies
    │   └── ReservationPolicy.php          # Business rules that don't fit in entity
    │
    └── Exception/
        ├── ReservationNotFoundException.php
        ├── InvalidReservationStateException.php
        └── MaxSpecialRequestsExceededException.php
```

---

## Domain Events

Domain events are recorded inside the Reservation aggregate via `recordEvent()` and dispatched by command handlers after persistence. Only the Reservation BC emits domain events.

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
│   EXAMPLE:                                   EXAMPLE:                                   │
│   ─────────                                  ─────────                                  │
│   class ReservationConfirmed                 class ReservationConfirmedEvent            │
│   {                                          implements IntegrationEvent                │
│       public function __construct(           {                                          │
│           public readonly ReservationId          public function __construct(           │
│               $reservationId,                        public readonly string $id,        │
│           public readonly DateTimeImmutable          public readonly string $roomType,  │
│               $occurredOn,                           public readonly string $checkIn,   │
│       ) {}                                           public readonly string $checkOut,  │
│   }                                                  public readonly string $guestEmail,│
│                                                      public readonly bool $isVip,       │
│   // Can reference domain objects                    public readonly DateTimeImmutable  │
│   // because it stays within BC                          $occurredAt,                   │
│                                                  ) {}                                   │
│                                              }                                          │
│                                                                                         │
│                                              // Only primitives and simple types        │
│                                              // Other BCs don't know our domain         │
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

> **Future:** When consumer BCs exist (e.g., Inventory), a message broker will replace the current log-only publisher to enable async cross-BC event delivery.

---

# CROSS-BC INTEGRATION

The system has three active integration paths, all using the Gateway + Adapter (ACL) pattern:

| From | To | Gateway (Port) | Adapter (ACL) | Mechanism |
|------|----|----------------|---------------|-----------|
| Reservation | Guest | `GuestGateway` | `GuestGatewayAdapter` → `GuestProfileApi` | Sync (direct call) |
| Reservation | Inventory | `InventoryGateway` | `InventoryGatewayAdapter` (stub) | Sync (hardcoded) |
| IAM | Guest | `GuestProfileGateway` | `GuestProfileGatewayAdapter` → `GuestProfileApi` | Sync (direct call) |

## Reservation → Guest BC

Reservation needs guest data (name, email, VIP status) to enrich integration events. The domain defines a port; the infrastructure adapter calls Guest BC's exposed API.

```php
// Reservation/Domain/Service/GuestGateway.php (PORT)
interface GuestGateway
{
    public function findByUuid(string $guestProfileId): ?GuestInfo;
}

// Reservation/Domain/Dto/GuestInfo.php
readonly class GuestInfo
{
    public function __construct(
        public string $guestProfileId,
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public bool $isVip,
    ) {}
}

// Reservation/Infrastructure/Integration/GuestGatewayAdapter.php (ACL)
// Calls GuestProfileApi and translates to Reservation's own DTO
class GuestGatewayAdapter implements GuestGateway
{
    public function __construct(
        private readonly GuestProfileApi $guestProfileApi,
    ) {}

    public function findByUuid(string $guestProfileId): ?GuestInfo
    {
        $data = $this->guestProfileApi->findByUuid($guestProfileId);
        if ($data === null) return null;

        $isVip = in_array($data->loyaltyTier, ['gold', 'platinum'], true);

        return new GuestInfo(
            guestProfileId: $data->uuid,
            fullName: $data->fullName,
            email: $data->email,
            phone: $data->phone,
            document: $data->document,
            isVip: $isVip,       // ACL: translates loyalty tier to boolean
        );
    }
}
```

## Reservation → Inventory BC (Stubbed)

Reservation checks room availability and fetches room type info before creating a reservation. The Inventory BC doesn't exist yet, so the adapter returns hardcoded data.

```php
// Reservation/Domain/Service/InventoryGateway.php (PORT)
interface InventoryGateway
{
    public function checkAvailability(string $roomType, ReservationPeriod $period): RoomAvailability;
    public function getRoomTypeInfo(string $roomType): RoomTypeInfo;
}

// Reservation/Domain/Dto/RoomAvailability.php
readonly class RoomAvailability
{
    public function __construct(
        public string $roomType,
        public int $availableCount,
        public float $pricePerNight,
    ) {}
}

// Reservation/Infrastructure/Integration/InventoryGatewayAdapter.php (STUB)
// TODO: Replace with real integration when Inventory BC is implemented
class InventoryGatewayAdapter implements InventoryGateway
{
    public function checkAvailability(string $roomType, ReservationPeriod $period): RoomAvailability
    {
        $prices = ['SINGLE' => 150.00, 'DOUBLE' => 250.00, 'SUITE' => 500.00];

        return new RoomAvailability(
            roomType: $roomType,
            availableCount: 10,                      // Always available (stub)
            pricePerNight: $prices[$roomType] ?? 200.00,
        );
    }
}
```

## IAM → Guest BC

When a new actor registers, IAM creates a guest profile via the gateway. This is how the Guest BC gets populated — there is no direct HTTP endpoint for creating guests.

```php
// IAM/Domain/Service/GuestProfileGateway.php (PORT)
interface GuestProfileGateway
{
    public function create(string $name, string $email, string $phone, string $document): string;
}

// IAM/Infrastructure/Integration/GuestProfileGatewayAdapter.php (ACL)
class GuestProfileGatewayAdapter implements GuestProfileGateway
{
    public function __construct(
        private GuestProfileApi $guestProfileApi,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): string
    {
        return $this->guestProfileApi->create(
            name: $name, email: $email, phone: $phone, document: $document,
        );
    }
}
```

## Guest BC — Exposed Integration API

The Guest BC exposes `GuestProfileApi` as an internal API for other BCs. It is **not** an HTTP endpoint — it's a PHP class resolved via the service container. Returns DTOs with primitives only.

```php
// Guest/Infrastructure/Integration/GuestProfileApi.php
class GuestProfileApi
{
    public function __construct(
        private CreateGuestProfileHandler $createHandler,
        private GuestProfileRepository $repository,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): string;
    public function findByUuid(string $uuid): ?GuestProfileData;
}

// Guest/Infrastructure/Integration/Dto/GuestProfileData.php
readonly class GuestProfileData
{
    public function __construct(
        public string $uuid,
        public string $fullName,
        public string $email,
        public string $phone,
        public string $document,
        public string $loyaltyTier,
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
│   │   │   ├── GuestGateway.php              # Port for Guest BC data
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
│       │   ├── GuestGatewayAdapter.php       # Implements GuestGateway
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
│       │   ├── Controllers/
│       │   │   └── ReservationController.php
│       │   ├── Requests/
│       │   │   ├── CreateReservationRequest.php
│       │   │   ├── CheckInRequest.php
│       │   │   ├── CancelReservationRequest.php
│       │   │   └── AddSpecialRequestRequest.php
│       │   └── Resources/
│       │       └── ReservationResource.php
│       │
│       ├── Routes/
│       │   └── api.php
│       │
│       └── Providers/
│           └── ReservationServiceProvider.php
│
├── Guest/                                    # BC: Guest
│   │
│   ├── Domain/
│   │   ├── GuestProfile.php                  # Aggregate Root
│   │   ├── GuestProfileId.php                # Identity VO
│   │   │
│   │   ├── ValueObject/
│   │   │   └── LoyaltyTier.php               # Enum (bronze, silver, gold, platinum)
│   │   │
│   │   ├── Repository/
│   │   │   └── GuestProfileRepository.php    # Interface
│   │   │
│   │   └── Exception/
│   │       └── GuestProfileNotFoundException.php
│   │
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateGuestProfile.php
│   │   │   ├── CreateGuestProfileHandler.php
│   │   │   ├── UpdateGuestProfile.php
│   │   │   └── UpdateGuestProfileHandler.php
│   │   │
│   │   └── Query/
│   │       ├── ListGuestProfiles.php
│   │       └── ListGuestProfilesHandler.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── GuestProfileReflector.php
│       │   └── Eloquent/
│       │       ├── GuestProfileModel.php
│       │       └── EloquentGuestProfileRepository.php
│       │
│       ├── Integration/                      # API exposed for other BCs
│       │   ├── GuestProfileApi.php           # Entry point for cross-BC access
│       │   └── Dto/
│       │       └── GuestProfileData.php      # DTO returned by the API
│       │
│       ├── Http/
│       │   ├── Controllers/
│       │   │   └── GuestProfileController.php
│       │   ├── Requests/
│       │   │   └── UpdateGuestProfileRequest.php
│       │   └── Resources/
│       │       └── GuestProfileResource.php
│       │
│       ├── Routes/
│       │   └── api.php
│       │
│       └── Providers/
│           └── GuestServiceProvider.php
│
├── IAM/                                      # BC: Identity & Access Management
│   │
│   ├── Domain/
│   │   ├── Actor.php                         # Aggregate Root
│   │   ├── ActorId.php                       # Identity VO
│   │   │
│   │   ├── ValueObject/
│   │   │   ├── ActorType.php                 # Enum (guest, system)
│   │   │   └── HashedPassword.php            # VO
│   │   │
│   │   ├── Repository/
│   │   │   └── ActorRepository.php           # Interface
│   │   │
│   │   ├── Service/
│   │   │   ├── PasswordHasher.php            # Interface
│   │   │   ├── TokenManager.php              # Interface
│   │   │   └── GuestProfileGateway.php       # Interface (port for Guest BC)
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
│   │       ├── AuthenticateActor.php
│   │       ├── AuthenticateActorHandler.php
│   │       ├── RevokeToken.php
│   │       └── RevokeTokenHandler.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── ActorReflector.php
│       │   └── Eloquent/
│       │       ├── ActorModel.php            # Eloquent model (for Sanctum)
│       │       └── EloquentActorRepository.php
│       │
│       ├── Integration/
│       │   └── GuestProfileGatewayAdapter.php
│       │
│       ├── Services/
│       │   ├── BcryptPasswordHasher.php
│       │   └── SanctumTokenManager.php
│       │
│       ├── Http/
│       │   ├── Controllers/
│       │   │   └── AuthController.php
│       │   ├── Requests/
│       │   │   ├── RegisterRequest.php
│       │   │   └── LoginRequest.php
│       │   └── Resources/
│       │       └── ActorResource.php
│       │
│       ├── Routes/
│       │   └── api.php
│       │
│       └── Providers/
│           └── IAMServiceProvider.php
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
        └── Messaging/
            └── LaravelEventDispatcher.php
```

---

# SUMMARY

| Aspect | Reservation | Guest | IAM |
|--------|------------|-------|-----|
| **Aggregate Root** | Reservation | GuestProfile | Actor |
| **Child Entities** | SpecialRequest | — | — |
| **Value Objects** | ReservationPeriod, ReservationStatus, RequestType, RequestStatus, SpecialRequestId | LoyaltyTier | ActorType, HashedPassword |
| **DTOs** | GuestInfo, RoomAvailability, RoomTypeInfo | GuestProfileData (integration) | — |
| **Domain Events** | 7 (internal) | — | — |
| **Integration Events** | 4 (published) | — | — |
| **Cross-BC Ports** | GuestGateway, InventoryGateway | — | GuestProfileGateway |
