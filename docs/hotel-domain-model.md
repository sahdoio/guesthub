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

## Domain Events vs Integration Events

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                         │
│   DOMAIN EVENTS                              INTEGRATION EVENTS                         │
│   (Internal to BC)                           (Cross-BC communication)                   │
│                                                                                         │
│   ┌─────────────────────────────┐            ┌─────────────────────────────┐           │
│   │ ReservationConfirmed        │            │ ReservationConfirmedEvent   │           │
│   │                             │            │                             │           │
│   │ • Used within Reservation   │   ───►     │ • Published to message      │           │
│   │   BC for side effects       │  mapped    │   broker                    │           │
│   │ • Triggers internal         │    to      │ • Consumed by other BCs     │           │
│   │   handlers                  │            │ • Contains only data other  │           │
│   │ • Rich domain object refs   │            │   BCs need (no domain refs) │           │
│   └─────────────────────────────┘            └─────────────────────────────┘           │
│                                                                                         │
│   WHERE THEY LIVE:                           WHERE THEY LIVE:                          │
│   Domain/Event/                              Application/IntegrationEvent/              │
│                                              (or Shared/IntegrationEvent/)              │
│                                                                                         │
│   EXAMPLE:                                   EXAMPLE:                                   │
│   ─────────                                  ─────────                                  │
│   class ReservationConfirmed                 class ReservationConfirmedEvent            │
│   {                                          {                                          │
│       public function __construct(               public function __construct(           │
│           public readonly Reservation $r,            public readonly string $id,        │
│           public readonly DateTimeImmutable          public readonly string $roomType,  │
│       ) {}                                           public readonly string $checkIn,   │
│   }                                                  public readonly string $checkOut,  │
│                                                      public readonly string $guestEmail,│
│   // Can reference domain objects                    public readonly bool $isVip,       │
│   // because it stays within BC                      public readonly DateTimeImmutable  │
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
┌──────────────┐     ┌──────────────────┐     ┌─────────────────────┐     ┌─────────────┐
│              │     │                  │     │                     │     │             │
│ Reservation  │────►│  Domain Event    │────►│  Application Layer  │────►│ Integration │
│ .confirm()   │     │  Raised          │     │  Handler maps to    │     │ Event       │
│              │     │                  │     │  Integration Event  │     │ Published   │
└──────────────┘     └──────────────────┘     └─────────────────────┘     └─────────────┘
                                                                                 │
                                                                                 ▼
                                                                          Message Broker
                                                                                 │
                     ┌───────────────────────────────────────────────────────────┘
                     │
                     ▼
              ┌─────────────┐     ┌──────────────────────┐     ┌──────────────────┐
              │ Integration │     │                      │     │                  │
              │ Event       │────►│  BC2 Event Handler   │────►│  BC2 Domain      │
              │ Consumed    │     │  (Application Layer) │     │  Operations      │
              └─────────────┘     └──────────────────────┘     └──────────────────┘
```

---

## Domain Events (BC1 — Internal)

| Event | Trigger | Used For (within BC) |
|-------|---------|---------------------|
| `ReservationCreated` | constructor | Send welcome email, log analytics |
| `ReservationConfirmed` | confirm() | Trigger payment capture, map to integration event |
| `GuestCheckedIn` | checkIn() | Update internal metrics, notify concierge |
| `GuestCheckedOut` | checkOut() | Generate invoice, request review |
| `ReservationCancelled` | cancel() | Process refund, send notification |
| `SpecialRequestAdded` | addSpecialRequest() | Internal notification |
| `SpecialRequestFulfilled` | fulfillSpecialRequest() | Log for quality metrics |

---

## Integration Events (Published by BC1)

| Event | When Published | Consumed By | Purpose |
|-------|---------------|-------------|---------|
| `ReservationConfirmedEvent` | After ReservationConfirmed | BC2: Inventory | Block a room |
| `ReservationCancelledEvent` | After ReservationCancelled | BC2: Inventory | Release room |
| `GuestCheckedInEvent` | After GuestCheckedIn | BC2: Inventory | Mark room occupied |
| `GuestCheckedOutEvent` | After GuestCheckedOut | BC2: Inventory | Release room, trigger cleaning |

---

# BC2: INVENTORY CONTEXT (PLANNED — NOT YET IMPLEMENTED)

> **Note:** This BC is a design target. The code does not exist yet. The Reservation BC's `InventoryGateway` is currently stubbed with hardcoded data.

## Aggregate: Room

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                  ROOM AGGREGATE                                         │
│                                                                                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐ │
│  │                          Room (Aggregate Root)                                     │ │
│  │                                                                                    │ │
│  │  Identity:                                                                         │ │
│  │  • id: RoomId                                                                      │ │
│  │  • number: RoomNumber (natural key, e.g., "201")                                   │ │
│  │                                                                                    │ │
│  │  State:                                                                            │ │
│  │  • type: RoomType                                                                  │ │
│  │  • status: RoomStatus                                                              │ │
│  │  • floor: int                                                                      │ │
│  │                                                                                    │ │
│  │  Compositions:                                                                     │ │
│  │  • details: RoomDetails (VO)                                                       │ │
│  │  • blockings: Blocking[] (Entities)  ◄── ENTITIES with own identity               │ │
│  │  • currentMaintenance: ?MaintenanceWindow (VO)                                     │ │
│  │                                                                                    │ │
│  │  Behavior:                                                                         │ │
│  │  • block(reservationId, period): BlockingId                                        │ │
│  │  • release(blockingId): void                                                       │ │
│  │  • releaseByReservation(reservationId): void                                       │ │
│  │  • startMaintenance(reason, until): void                                           │ │
│  │  • endMaintenance(): void                                                          │ │
│  │  • markOccupied(): void                                                            │ │
│  │  • markAvailable(): void                                                           │ │
│  │  • isAvailableFor(period): bool                                                    │ │
│  │                                                                                    │ │
│  └───────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                         │
│         │ contains                    │ contains                  │ contains            │
│         ▼                             ▼                           ▼                     │
│                                                                                         │
│  ┌─────────────────────┐    ┌─────────────────────┐    ┌──────────────────────────────┐│
│  │    RoomDetails      │    │  MaintenanceWindow  │    │        Blocking              ││
│  │   (Value Object)    │    │   (Value Object)    │    │        (Entity)              ││
│  │                     │    │                     │    │                              ││
│  │ • description       │    │ • reason: string    │    │ • id: BlockingId        ◄─── ││
│  │ • maxOccupancy      │    │ • from: DateTime    │    │ • reservationId: string own  ││
│  │ • pricePerNight     │    │ • until: DateTime   │    │ • period: BlockingPeriod ID  ││
│  │ • amenities[]       │    │                     │    │ • blockedAt: DateTime        ││
│  │                     │    │ Immutable           │    │ • status: BlockingStatus     ││
│  │ Immutable           │    │ (replaced entirely  │    │                              ││
│  │                     │    │ on each change)     │    │ Mutable:                     ││
│  └─────────────────────┘    └─────────────────────┘    │ • release(): void            ││
│                                                        │ • extend(newEnd): void       ││
│                                                        │                              ││
│                                                        │ Lifecycle tied to Room       ││
│                                                        │ but individually addressable ││
│                                                        └──────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

### Why Blocking is an ENTITY:

1. **Has Identity** — `BlockingId` allows "release blocking X"
2. **Changes State** — Can be released, extended
3. **Individual Operations** — Need to find "the blocking for reservation Y"
4. **Multiple Per Room** — Room can have multiple future blockings

---

## Domain Layer Structure (BC2)

```
Inventory/
└── Domain/
    ├── Room.php                           # Aggregate Root (Entity)
    ├── RoomId.php                         # Identity Value Object
    │
    ├── Entity/
    │   └── Blocking.php                   # Child Entity
    │
    ├── ValueObject/
    │   ├── RoomNumber.php                 # VO
    │   ├── RoomDetails.php                # VO
    │   ├── MaintenanceWindow.php          # VO
    │   ├── BlockingId.php                 # Identity VO for child entity
    │   ├── BlockingPeriod.php             # VO (similar to ReservationPeriod)
    │   ├── Money.php                      # VO
    │   ├── RoomType.php                   # Enum
    │   ├── RoomStatus.php                 # Enum
    │   └── BlockingStatus.php             # Enum (active, released)
    │
    ├── Event/                             # DOMAIN EVENTS (internal)
    │   ├── RoomCreated.php
    │   ├── RoomBlocked.php
    │   ├── RoomReleased.php
    │   ├── RoomMarkedOccupied.php
    │   ├── RoomMarkedAvailable.php
    │   ├── MaintenanceStarted.php
    │   └── MaintenanceEnded.php
    │
    ├── Repository/
    │   └── RoomRepository.php             # Interface only
    │
    └── Exception/
        ├── RoomNotFoundException.php
        ├── RoomNotAvailableException.php
        └── BlockingNotFoundException.php
```

---

# CROSS-BC INTEGRATION

## Synchronous Integration (Interface-based, for immediate response)

BC1 needs to check room availability **before** creating a reservation. This requires a sync call.

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                         │
│   BC1: RESERVATION                                BC2: INVENTORY                        │
│                                                                                         │
│   ┌─────────────────────────────────┐            ┌─────────────────────────────────┐   │
│   │  Domain/                        │            │  Domain/                        │   │
│   │                                 │            │                                 │   │
│   │  Service/                       │            │                                 │   │
│   │  └── InventoryGateway.php       │            │                                 │   │
│   │      (INTERFACE - port)         │            │                                 │   │
│   │                                 │            │                                 │   │
│   │  interface InventoryGateway {   │            │                                 │   │
│   │    checkAvailability(           │            │                                 │   │
│   │      RoomType, Period           │            │                                 │   │
│   │    ): Availability;             │            │                                 │   │
│   │                                 │            │                                 │   │
│   │    getRoomTypeInfo(             │            │                                 │   │
│   │      RoomType                   │            │                                 │   │
│   │    ): RoomTypeInfo;             │            │                                 │   │
│   │  }                              │            │                                 │   │
│   └────────────────┬────────────────┘            └─────────────────────────────────┘   │
│                    │                                                                    │
│                    │ implements                                                         │
│                    ▼                                                                    │
│   ┌─────────────────────────────────┐            ┌─────────────────────────────────┐   │
│   │  Infrastructure/                │            │  Infrastructure/                │   │
│   │                                 │            │                                 │   │
│   │  Integration/                   │   calls    │  Api/                           │   │
│   │  └── InventoryGatewayAdapter.php│───────────►│  └── InventoryApi.php           │   │
│   │      (ADAPTER - ACL)            │  directly  │      (exposed internally)       │   │
│   │                                 │            │                                 │   │
│   │  class InventoryGatewayAdapter  │            │  class InventoryApi {           │   │
│   │    implements InventoryGateway  │            │    __construct(                 │   │
│   │  {                              │            │      RoomRepository $repo       │   │
│   │    __construct(                 │            │    )                            │   │
│   │      InventoryApi $api  ◄───────│────────────│                                 │   │
│   │    )                            │            │    checkAvailability(           │   │
│   │                                 │            │      string $roomType,          │   │
│   │    // Translates BC2 response   │            │      string $from,              │   │
│   │    // to BC1 domain language    │            │      string $to                 │   │
│   │  }                              │            │    ): array                     │   │
│   │                                 │            │                                 │   │
│   └─────────────────────────────────┘            │    // Returns primitives,       │   │
│                                                  │    // not domain objects        │   │
│                                                  │  }                              │   │
│                                                  └─────────────────────────────────┘   │
│                                                                                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

### The ACL (Anti-Corruption Layer) Role

```php
// BC1: Domain/Service/InventoryGateway.php (INTERFACE)
// ────────────────────────────────────────────────────
// Uses BC1's own types — doesn't know BC2 exists

interface InventoryGateway
{
    public function checkAvailability(
        RoomType $type,                    // BC1's RoomType enum
        ReservationPeriod $period          // BC1's value object
    ): RoomAvailability;                   // BC1's DTO

    public function getRoomTypeInfo(
        RoomType $type
    ): RoomTypeInfo;                       // BC1's DTO
}

// BC1: Domain/Dto/RoomAvailability.php
// ────────────────────────────────────
readonly class RoomAvailability
{
    public function __construct(
        public bool $isAvailable,
        public int $availableCount
    ) {}
}

// BC1: Infrastructure/Integration/InventoryGatewayAdapter.php (ADAPTER)
// ─────────────────────────────────────────────────────────────────────
// Knows how to talk to BC2 and translate responses

class InventoryGatewayAdapter implements InventoryGateway
{
    public function __construct(
        private InventoryApi $inventoryApi   // BC2's internal API
    ) {}

    public function checkAvailability(
        RoomType $type,
        ReservationPeriod $period
    ): RoomAvailability {
        // Call BC2 API (direct call, not HTTP)
        $result = $this->inventoryApi->checkAvailability(
            roomType: $type->value,                    // Convert to primitive
            from: $period->checkIn()->format('Y-m-d'),
            to: $period->checkOut()->format('Y-m-d')
        );

        // Translate BC2 response to BC1 language (ACL)
        return new RoomAvailability(
            isAvailable: $result['available_count'] > 0,
            availableCount: $result['available_count']
        );
    }
}
```

### BC2 Internal API (Infrastructure Layer)

```php
// BC2: Infrastructure/Api/InventoryApi.php
// ────────────────────────────────────────
// Exposed for other BCs, returns primitives only

class InventoryApi
{
    public function __construct(
        private RoomRepository $roomRepository
    ) {}

    /**
     * @return array{available_count: int, rooms: array}
     */
    public function checkAvailability(
        string $roomType,
        string $from,
        string $to
    ): array {
        $period = new BlockingPeriod(
            new DateTimeImmutable($from),
            new DateTimeImmutable($to)
        );

        $rooms = $this->roomRepository->findAvailableByType(
            RoomType::from($roomType),
            $period
        );

        return [
            'available_count' => count($rooms),
            'rooms' => array_map(
                fn(Room $room) => [
                    'room_number' => $room->number()->value(),
                    'floor' => $room->floor(),
                ],
                $rooms
            )
        ];
    }

    public function getRoomTypeInfo(string $roomType): array
    {
        // Returns info about room type (price, amenities, etc.)
        // ...
    }
}
```

---

## Asynchronous Integration (Events, for eventual consistency)

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                         │
│  BC1: RESERVATION                                  BC2: INVENTORY                       │
│                                                                                         │
│  ┌───────────────────────┐                        ┌───────────────────────┐            │
│  │ Application/          │                        │ Application/          │            │
│  │                       │                        │                       │            │
│  │ EventHandler/         │                        │ IntegrationHandler/   │            │
│  │ └── OnReservation     │                        │ └── OnReservation     │            │
│  │     Confirmed.php     │                        │     ConfirmedEvent.php│            │
│  │                       │                        │                       │            │
│  │ // Listens to DOMAIN  │                        │ // Listens to         │            │
│  │ // event, publishes   │                        │ // INTEGRATION event  │            │
│  │ // INTEGRATION event  │                        │ // from message broker│            │
│  └───────────┬───────────┘                        └───────────┬───────────┘            │
│              │                                                │                         │
│              │  publishes                          consumes   │                         │
│              ▼                                                ▼                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐ │
│  │                                                                                   │ │
│  │                              MESSAGE BROKER                                       │ │
│  │                                                                                   │ │
│  │   Queue: reservation.confirmed                                                    │ │
│  │   ┌─────────────────────────────────────────────────────────────────────────┐    │ │
│  │   │ {                                                                        │    │ │
│  │   │   "event_type": "reservation.confirmed",                                 │    │ │
│  │   │   "event_id": "uuid",                                                    │    │ │
│  │   │   "occurred_at": "2025-02-09T10:30:00Z",                                 │    │ │
│  │   │   "payload": {                                                           │    │ │
│  │   │     "reservation_id": "uuid",                                            │    │ │
│  │   │     "room_type": "deluxe",                                               │    │ │
│  │   │     "check_in": "2025-03-01",                                            │    │ │
│  │   │     "check_out": "2025-03-05",                                           │    │ │
│  │   │     "guest_name": "John Doe",                                            │    │ │
│  │   │     "is_vip": false                                                      │    │ │
│  │   │   }                                                                      │    │ │
│  │   │ }                                                                        │    │ │
│  │   └─────────────────────────────────────────────────────────────────────────┘    │ │
│  │                                                                                   │ │
│  └───────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
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
