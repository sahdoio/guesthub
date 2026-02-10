# Hotel Management System - DDD Implementation with Claude Code

## 📋 PROJECT CONTEXT

### System Overview
A **Hotel Management System** built as a **Modular Monolith** following **Domain-Driven Design (DDD)** principles with **4 Bounded Contexts**:

1. **BC1: Reservation Context** - Manages booking lifecycle, guest reservations, and special requests
2. **BC2: Inventory Context** - Controls room availability, blocking, and maintenance
3. **BC3: Guest Context** - Handles guest profiles and preferences *(future)*
4. **BC4: Billing Context** - Processes payments and invoicing *(future)*

### Technology Stack
- **PHP 8.4**
- **Laravel Framework 12.50.0**
- **Architecture**: Clean Architecture + DDD Tactical Patterns
- **Persistence**: Eloquent ORM (Repository Pattern)
- **Messaging**: Laravel Events (Domain Events + Integration Events)

---

## 🏗️ ARCHITECTURAL STRUCTURE

### Directory Organization

```
src/BoundedContexts/
├── Reservation/                    # BC1
│   ├── Domain/                     # Pure business logic (no framework dependencies)
│   │   ├── Reservation.php         # Aggregate Root
│   │   ├── ReservationId.php       # Identity Value Object
│   │   ├── Entity/
│   │   │   └── SpecialRequest.php  # Child Entity (has identity, mutable)
│   │   ├── ValueObject/
│   │   │   ├── Guest.php           # Immutable, compared by value
│   │   │   ├── ReservationPeriod.php
│   │   │   ├── Email.php
│   │   │   ├── Phone.php
│   │   │   ├── ReservationStatus.php  # Enum
│   │   │   ├── RequestType.php
│   │   │   └── RequestStatus.php
│   │   ├── Event/                  # Domain Events (internal to BC)
│   │   │   ├── ReservationCreated.php
│   │   │   ├── ReservationConfirmed.php
│   │   │   ├── ReservationCancelled.php
│   │   │   ├── GuestCheckedIn.php
│   │   │   ├── GuestCheckedOut.php
│   │   │   ├── SpecialRequestAdded.php
│   │   │   └── SpecialRequestFulfilled.php
│   │   ├── Repository/
│   │   │   └── ReservationRepository.php  # Interface
│   │   ├── Service/
│   │   │   ├── InventoryGateway.php       # Interface (ACL port)
│   │   │   └── ReservationPolicy.php      # Domain service
│   │   ├── Dto/
│   │   │   ├── RoomAvailability.php
│   │   │   └── RoomTypeInfo.php
│   │   └── Exception/
│   │       ├── ReservationNotFoundException.php
│   │       ├── InvalidReservationStateException.php
│   │       └── MaxSpecialRequestsExceededException.php
│   │
│   ├── Application/                # Use cases & orchestration
│   │   ├── Command/
│   │   │   ├── CreateReservation.php
│   │   │   ├── ConfirmReservation.php
│   │   │   ├── CheckInGuest.php
│   │   │   ├── CheckOutGuest.php
│   │   │   ├── CancelReservation.php
│   │   │   └── AddSpecialRequest.php
│   │   ├── Handler/
│   │   │   ├── CreateReservationHandler.php
│   │   │   ├── ConfirmReservationHandler.php
│   │   │   └── ...
│   │   ├── Query/
│   │   │   ├── GetReservation.php
│   │   │   └── ListGuestReservations.php
│   │   ├── EventHandler/            # Handles Domain Events
│   │   │   ├── OnReservationConfirmed.php  # → publishes integration event
│   │   │   ├── OnGuestCheckedIn.php
│   │   │   └── ...
│   │   └── IntegrationEvent/        # Cross-BC communication
│   │       ├── ReservationConfirmedEvent.php
│   │       ├── ReservationCancelledEvent.php
│   │       ├── GuestCheckedInEvent.php
│   │       └── GuestCheckedOutEvent.php
│   │
│   ├── Infrastructure/              # Laravel-specific implementations
│   │   ├── Persistence/
│   │   │   ├── EloquentReservationRepository.php  # Repository implementation
│   │   │   └── Eloquent/
│   │   │       └── ReservationEloquent.php        # Eloquent model (ORM)
│   │   ├── Integration/             # ACL adapters
│   │   │   └── InventoryGatewayAdapter.php
│   │   └── Messaging/
│   │       └── IntegrationEventPublisher.php
│   │
│   └── Presentation/                # API layer
│       └── Http/
│           ├── Controllers/
│           │   └── ReservationController.php
│           ├── Requests/
│           │   └── CreateReservationRequest.php
│           └── Resources/
│               └── ReservationResource.php
│
├── Inventory/                       # BC2
│   ├── Domain/
│   │   ├── Room.php                 # Aggregate Root
│   │   ├── RoomId.php
│   │   ├── Entity/
│   │   │   └── Blocking.php         # Child Entity
│   │   ├── ValueObject/
│   │   │   ├── RoomNumber.php
│   │   │   ├── RoomDetails.php
│   │   │   ├── RoomType.php
│   │   │   └── RoomStatus.php
│   │   ├── Event/
│   │   │   ├── RoomCreated.php
│   │   │   ├── RoomBlocked.php
│   │   │   └── RoomReleased.php
│   │   └── Repository/
│   │       └── RoomRepository.php
│   ├── Application/
│   │   ├── Command/
│   │   ├── Handler/
│   │   └── IntegrationHandler/      # Handles events FROM BC1
│   │       ├── OnReservationConfirmedEvent.php
│   │       ├── OnReservationCancelledEvent.php
│   │       └── ...
│   └── Infrastructure/
│       ├── Persistence/
│       ├── Api/
│       │   └── InventoryApi.php     # Internal API (called by BC1's adapter)
│       └── Messaging/
│           └── IntegrationEventConsumer.php
│
└── Shared/                          # Shared Kernel (minimal)
    ├── Domain/
    │   ├── AggregateRoot.php
    │   ├── Entity.php
    │   ├── ValueObject.php
    │   ├── DomainEvent.php
    │   └── Identity.php
    └── Infrastructure/
        └── Messaging/
            ├── IntegrationEvent.php
            └── MessageBroker.php
```

---

## 🔄 COMMUNICATION BETWEEN BOUNDED CONTEXTS

### 1. **Domain Events** (Internal to BC)
**Purpose**: Notify components within the SAME bounded context about state changes.

**Characteristics**:
- Pure domain language
- Synchronous or asynchronous (within BC)
- Example: `ReservationConfirmed`, `SpecialRequestAdded`

**Flow**:
```
Reservation Aggregate
    ↓ (raises domain event)
ReservationConfirmed (Domain Event)
    ↓ (handled by)
Application Layer EventHandler
    ↓ (transforms & publishes)
Integration Event
```

---

### 2. **Integration Events** (Cross-BC Communication)
**Purpose**: Notify OTHER bounded contexts about significant business events.

**Characteristics**:
- Asynchronous (via Laravel Events/Queue)
- Decoupled - sender doesn't know consumers
- DTOs with primitive types (no domain objects)
- Published by Application layer, not Domain

**Example Flow**:
```
BC1: Reservation Context
    │
    │ 1. User confirms reservation
    ↓
Domain: Reservation.confirm()
    │
    │ 2. Raises Domain Event
    ↓
Domain Event: ReservationConfirmed
    │
    │ 3. Application EventHandler catches it
    ↓
Application: OnReservationConfirmed Handler
    │
    │ 4. Publishes Integration Event
    ↓
Integration Event: ReservationConfirmedEvent (via Laravel Event Bus)
    │
    ├──→ BC2: Inventory Context
    │    └── OnReservationConfirmedEvent Handler
    │        └── Blocks room for reservation period
    │
    ├──→ BC3: Guest Context
    │    └── Updates guest loyalty points
    │
    └──→ BC4: Billing Context
         └── Creates invoice
```

**Key Principle**: Each BC remains autonomous. If BC2 is down, BC1 still completes the reservation.

---

### 3. **Anti-Corruption Layer (ACL)** - Synchronous Queries

**Purpose**: When one BC needs data from another RIGHT NOW (e.g., checking room availability).

**Pattern**:
```
BC1: Reservation Context
    │
Application: CreateReservationHandler
    │
    │ (needs to check if rooms available)
    ↓
Domain Service: InventoryGateway (interface)
    │
Infrastructure: InventoryGatewayAdapter (implementation)
    │
    │ (calls internal API)
    ↓
BC2: Inventory Context
    │
Infrastructure: InventoryApi
    │
    │ (queries domain)
    ↓
Domain: Room Repository
```

**Implementation in Modular Monolith**:
- BC1 defines an **interface** (`InventoryGateway`) in its Domain layer
- BC1's Infrastructure implements the adapter (`InventoryGatewayAdapter`)
- BC2 exposes an **internal API** (`InventoryApi`) in its Infrastructure layer
- The adapter directly calls BC2's API (no HTTP, just method calls)

**Important**: 
- No direct database access between BCs
- BC1 uses DTOs (`RoomAvailability`), not BC2's domain objects
- Adapter translates between BC1's and BC2's languages

---

### 4. **Shared Kernel** (Minimize This!)

**Purpose**: Truly universal concepts that ALL contexts agree on.

**What Goes Here**:
- Base abstractions: `AggregateRoot`, `Entity`, `ValueObject`, `DomainEvent`
- Universal VOs: `Email`, `Phone`, `Money` (if standardized across all BCs)
- Event infrastructure: `IntegrationEvent`, `MessageBroker`

**What DOESN'T Go Here**:
- Business logic
- Domain-specific value objects (e.g., `ReservationStatus` stays in BC1)
- Any concept that might evolve differently per context

---

## 📊 KEY DESIGN DECISIONS

### Entity vs Value Object Decision Matrix

| Aspect | Entity | Value Object |
|--------|--------|--------------|
| **Identity** | Has unique ID (can reference it) | No identity (compared by entire value) |
| **Mutability** | Can change state over time | Immutable (replace entirely to "update") |
| **Lifecycle** | Tracked over time (created, modified) | Replaceable (no history) |
| **Equality** | By ID: `$entity->id === $other->id` | By value: `$vo->equals($other)` |

**Examples from BC1**:
- **SpecialRequest = Entity**: Has `SpecialRequestId`, can be fulfilled (state change), tracked over time
- **Guest = Value Object**: No separate ID, immutable, replace entire Guest to update email

---

### Aggregate Boundaries

**Reservation Aggregate Includes**:
- `Reservation` (root)
- `SpecialRequest[]` (child entities)
- `Guest` (VO)
- `ReservationPeriod` (VO)

**Why This Boundary**:
- SpecialRequests cannot exist without a Reservation
- All mutations go through `Reservation` methods
- Transaction boundary = 1 Reservation + its children

**What's NOT in the Aggregate**:
- Rooms (belong to Inventory BC)
- Invoices (belong to Billing BC)
- Guest profile (would belong to Guest BC)

---

### Repository Pattern

**Interface** (in Domain layer):
```php
interface ReservationRepository
{
    public function save(Reservation $reservation): void;
    public function findById(ReservationId $id): ?Reservation;
    public function nextIdentity(): ReservationId;
}
```

**Implementation** (in Infrastructure layer):
```php
class EloquentReservationRepository implements ReservationRepository
{
    // Uses ReservationEloquent model
    // Maps domain objects ↔ Eloquent models
}
```

**Why**:
- Domain layer doesn't depend on Laravel/Eloquent
- Testable with in-memory repositories
- Can swap persistence layer without touching domain logic

---

## 🎯 IMPLEMENTATION STRATEGY FOR BC1

### Phase 1: Foundation
1. Shared Kernel base classes
2. Directory structure

### Phase 2: Domain Layer (Pure PHP)
3. Value Objects (Email, Phone, ReservationPeriod, Guest)
4. Enums (ReservationStatus, RequestType, RequestStatus)
5. Identity VOs (ReservationId, SpecialRequestId)
6. Domain Events
7. SpecialRequest Entity
8. Reservation Aggregate Root
9. Repository Interface
10. Domain Service & Gateway Interface
11. Domain Exceptions

### Phase 3: Application Layer
12. Commands (DTOs)
13. Command Handlers
14. Integration Events
15. Integration Event Handlers

### Phase 4: Infrastructure Layer
16. Eloquent Models
17. Repository Implementation
18. ACL Adapter (InventoryGateway)
19. Event Publishers

### Phase 5: Presentation Layer
20. Controllers
21. Form Requests
22. API Resources
23. Routes

---

## 🚀 CLAUDE CODE PROMPTS

### PROMPT 1: Create Project Structure

Create the complete directory structure for BC1: Reservation Context following DDD and Clean Architecture principles:

```
src/BoundedContexts/Reservation/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Event/
│   ├── Repository/
│   ├── Service/
│   ├── Dto/
│   └── Exception/
├── Application/
│   ├── Command/
│   ├── Handler/
│   ├── Query/
│   ├── EventHandler/
│   └── IntegrationEvent/
├── Infrastructure/
│   ├── Persistence/
│   │   └── Eloquent/
│   ├── Integration/
│   └── Messaging/
└── Presentation/
    └── Http/
        ├── Controllers/
        ├── Requests/
        └── Resources/
```

Also create the Shared Kernel structure:
```
src/Shared/
├── Domain/
└── Infrastructure/
    └── Messaging/
```

Create all directories and add `.gitkeep` files to maintain the structure.

---

### PROMPT 2: Implement Shared Kernel Base Classes

Create the base abstractions in `src/Shared/Domain/`:

1. **AggregateRoot.php** - Abstract base class for aggregate roots with domain event tracking
2. **Entity.php** - Abstract base class for entities with identity
3. **ValueObject.php** - Abstract base class for immutable value objects with `equals()` method
4. **Identity.php** - Abstract base class for identity value objects (UUIDs)
5. **DomainEvent.php** - Interface for domain events with `occurredOn(): DateTimeImmutable`
6. **Identity.php** - Abstract base class for identity value objects (UUIDs)

Use PHP 8.4 features (typed properties, readonly, enums). Add proper docblocks.

---

### PROMPT 3: Implement Email Value Object

### PROMPT 3: Implement Email Value Object

Create `src/BoundedContexts/Reservation/Domain/ValueObject/Email.php`:

- Extends `Shared\Domain\ValueObject`
- Readonly string property
- Validates email format in constructor
- Throws `InvalidArgumentException` if invalid
- Implements `equals()` method
- Implements `__toString()` method
- Add static factory method `fromString(string $email): self`

---

### PROMPT 4: Implement Phone Value Object

Create `src/BoundedContexts/Reservation/Domain/ValueObject/Phone.php`:

- Extends `Shared\Domain\ValueObject`
- Readonly string property
- Validates phone format (E.164 format: +[country code][number])
- Throws `InvalidArgumentException` if invalid
- Implements `equals()` method
- Implements `__toString()` method
- Add static factory method `fromString(string $phone): self`

---

### PROMPT 5: Implement Enums

Create three enums in `src/BoundedContexts/Reservation/Domain/ValueObject/`:

1. **ReservationStatus.php** (backed by string):
   - PENDING
   - CONFIRMED
   - CHECKED_IN
   - CHECKED_OUT
   - CANCELLED

2. **RequestType.php** (backed by string):
   - EARLY_CHECK_IN
   - LATE_CHECK_OUT
   - EXTRA_BED
   - DIETARY_RESTRICTION
   - SPECIAL_OCCASION
   - OTHER

3. **RequestStatus.php** (backed by string):
   - PENDING
   - FULFILLED
   - CANCELLED

Add method `label(): string` to each enum for human-readable names.

---

### PROMPT 6: Implement ReservationPeriod Value Object

Create `src/BoundedContexts/Reservation/Domain/ValueObject/ReservationPeriod.php`:

- Extends `Shared\Domain\ValueObject`
- Readonly properties: `checkIn` and `checkOut` (DateTimeImmutable)
- Constructor validates:
  - checkOut must be after checkIn
  - checkIn cannot be in the past
  - Maximum stay 365 days
- Derived methods:
  - `nights(): int` - calculates number of nights
  - `overlaps(ReservationPeriod $other): bool`
  - `contains(DateTimeImmutable $date): bool`
- Implements `equals()` method

---

### PROMPT 7: Implement Guest Value Object

Create `src/BoundedContexts/Reservation/Domain/ValueObject/Guest.php`:

- Extends `Shared\Domain\ValueObject`
- Readonly properties:
  - `fullName`: string
  - `email`: Email
  - `phone`: Phone
  - `document`: string (CPF/passport)
  - `isVip`: bool
- Constructor validates fullName is not empty
- Implements `equals()` method
- Static factory method `create(...): self`

---

### PROMPT 8: Implement Identity Value Objects

Create two identity VOs in `src/BoundedContexts/Reservation/Domain/`:

1. **ReservationId.php** - Extends `Shared\Domain\Identity`
   - Uses UUID v4
   - Static methods: `generate()`, `fromString(string $id)`

2. **ValueObject/SpecialRequestId.php** - Extends `Shared\Domain\Identity`
   - Uses UUID v4
   - Static methods: `generate()`, `fromString(string $id)`

Both implement `__toString()` and `equals()`.

---

### PROMPT 9: Implement Domain Events

Create domain events in `src/BoundedContexts/Reservation/Domain/Event/`:

1. **ReservationCreated.php**
2. **ReservationConfirmed.php**
3. **ReservationCancelled.php**
4. **GuestCheckedIn.php**
5. **GuestCheckedOut.php**
6. **SpecialRequestAdded.php**
7. **SpecialRequestFulfilled.php**

Each implements `DomainEvent` interface and contains:
- `readonly ReservationId $reservationId`
- `readonly DateTimeImmutable $occurredOn`
- Constructor that auto-sets `occurredOn`
- Getter methods

For events specific to special requests, add `readonly SpecialRequestId $requestId`.

---

### PROMPT 10: Implement SpecialRequest Entity

Create `src/BoundedContexts/Reservation/Domain/Entity/SpecialRequest.php`:

- Extends `Shared\Domain\Entity`
- Properties:
  - `readonly SpecialRequestId $id`
  - `RequestType $type`
  - `string $description`
  - `RequestStatus $status`
  - `?DateTimeImmutable $fulfilledAt`
  - `readonly DateTimeImmutable $createdAt`
- Constructor takes id, type, description; sets status to PENDING
- Methods:
  - `fulfill(): void` - changes status, sets fulfilledAt, validates not already fulfilled
  - `changeDescription(string $newDescription): void`
  - `cancel(): void`
  - Getters for all properties

Throw `InvalidReservationStateException` for invalid state transitions.

---

### PROMPT 11: Implement Reservation Aggregate Root (Part 1 - Structure)

Create `src/BoundedContexts/Reservation/Domain/Reservation.php`:

- Extends `Shared\Domain\AggregateRoot`
- Properties:
  - `readonly ReservationId $id`
  - `ReservationStatus $status`
  - `Guest $guest`
  - `ReservationPeriod $period`
  - `string $roomType`
  - `?string $assignedRoomNumber`
  - `array $specialRequests` (SpecialRequest[])
  - Timestamps: `createdAt`, `confirmedAt`, `checkedInAt`, `checkedOutAt`, `cancelledAt`
  - `?string $cancellationReason`

Constructor:
- Takes: id, guest, period, roomType
- Sets status to PENDING
- Initializes empty specialRequests array
- Records ReservationCreated domain event

Add only getters in this prompt. Behavior methods in next prompt.

---

### PROMPT 12: Implement Reservation Aggregate Root (Part 2 - Behavior)

Add behavior methods to `src/BoundedContexts/Reservation/Domain/Reservation.php`:

1. **confirm(): void**
   - Validates status is PENDING
   - Changes status to CONFIRMED
   - Sets confirmedAt timestamp
   - Records ReservationConfirmed event

2. **checkIn(string $roomNumber): void**
   - Validates status is CONFIRMED
   - Changes status to CHECKED_IN
   - Sets assignedRoomNumber
   - Sets checkedInAt timestamp
   - Records GuestCheckedIn event

3. **checkOut(): void**
   - Validates status is CHECKED_IN
   - Changes status to CHECKED_OUT
   - Sets checkedOutAt timestamp
   - Records GuestCheckedOut event

4. **cancel(string $reason): void**
   - Validates status is PENDING or CONFIRMED (cannot cancel after check-in)
   - Changes status to CANCELLED
   - Sets cancellationReason and cancelledAt
   - Records ReservationCancelled event

Throw `InvalidReservationStateException` for invalid state transitions.

---

### PROMPT 13: Implement Reservation Aggregate Root (Part 3 - Special Requests)

Add special request management methods to `src/BoundedContexts/Reservation/Domain/Reservation.php`:

1. **addSpecialRequest(RequestType $type, string $description): SpecialRequestId**
   - Validates reservation is not CANCELLED or CHECKED_OUT
   - Validates max 5 special requests (throw MaxSpecialRequestsExceededException)
   - Creates new SpecialRequest with generated ID
   - Adds to specialRequests array
   - Records SpecialRequestAdded event
   - Returns the new SpecialRequestId

2. **fulfillSpecialRequest(SpecialRequestId $requestId): void**
   - Finds request by ID (throw exception if not found)
   - Calls request's fulfill() method
   - Records SpecialRequestFulfilled event

3. **removeSpecialRequest(SpecialRequestId $requestId): void**
   - Finds and removes request from array
   - Only allowed if status is PENDING

4. **changeGuestContact(Email $email, Phone $phone): void**
   - Creates new Guest VO with updated contact info
   - Replaces the guest property (VO immutability pattern)

---

### PROMPT 14: Implement Domain Exceptions

Create domain exceptions in `src/BoundedContexts/Reservation/Domain/Exception/`:

1. **ReservationNotFoundException.php**
   - Extends `DomainException`
   - Static factory: `withId(ReservationId $id): self`

2. **InvalidReservationStateException.php**
   - Extends `DomainException`
   - Static factory: `forTransition(ReservationStatus $from, ReservationStatus $to): self`

3. **MaxSpecialRequestsExceededException.php**
   - Extends `DomainException`
   - Constructor with default message

All exceptions should have proper error messages.

---

### PROMPT 15: Implement Repository Interface and DTOs

Create in `src/BoundedContexts/Reservation/Domain/`:

1. **Repository/ReservationRepository.php** (interface):
```php
interface ReservationRepository
{
    public function save(Reservation $reservation): void;
    public function findById(ReservationId $id): ?Reservation;
    public function findByGuestEmail(Email $email): array;
    public function nextIdentity(): ReservationId;
}
```

2. **Dto/RoomAvailability.php**:
   - readonly properties: `roomType` (string), `availableCount` (int), `pricePerNight` (float)

3. **Dto/RoomTypeInfo.php**:
   - readonly properties: `type` (string), `description` (string), `capacity` (int), `amenities` (array)

---

### PROMPT 16: Implement Domain Service and Gateway Interface

Create in `src/BoundedContexts/Reservation/Domain/Service/`:

1. **InventoryGateway.php** (interface - ACL port):
```php
interface InventoryGateway
{
    public function checkAvailability(
        string $roomType, 
        ReservationPeriod $period
    ): RoomAvailability;
    
    public function getRoomTypeInfo(string $roomType): RoomTypeInfo;
}
```

2. **ReservationPolicy.php** (domain service):
   - Dependency: InventoryGateway
   - Method: `canCreateReservation(Guest $guest, ReservationPeriod $period, string $roomType): bool`
   - Business rules:
     - Check room availability via gateway
     - VIP guests can book up to 90 days in advance
     - Regular guests can book up to 60 days in advance
     - Minimum stay 1 night

---

### PROMPT 17: Implement Application Commands

Create command DTOs in `src/BoundedContexts/Reservation/Application/Command/`:

1. **CreateReservation.php**
2. **ConfirmReservation.php**
3. **CheckInGuest.php**
4. **CheckOutGuest.php**
5. **CancelReservation.php**
6. **AddSpecialRequest.php**

Each should be a readonly class with properties matching the use case parameters. Use PHP 8.4 promoted constructor properties.

Example for CreateReservation:
- guestFullName, guestEmail, guestPhone, guestDocument, isVip
- checkIn, checkOut (DateTimeImmutable)
- roomType

---

### PROMPT 18: Implement CreateReservationHandler

Create `src/BoundedContexts/Reservation/Application/Handler/CreateReservationHandler.php`:

- Dependencies (constructor injection):
  - ReservationRepository
  - ReservationPolicy
  - Laravel Event Dispatcher

- Method: `handle(CreateReservation $command): ReservationId`

- Logic:
  1. Create Guest VO from command data
  2. Create ReservationPeriod VO
  3. Check policy: `$policy->canCreateReservation(...)`
  4. Generate new ReservationId
  5. Create Reservation aggregate
  6. Save via repository
  7. Dispatch all domain events from aggregate
  8. Return ReservationId

Add proper validation and exception handling.

---

### PROMPT 19: Implement Other Command Handlers

Create handlers in `src/BoundedContexts/Reservation/Application/Handler/`:

1. **ConfirmReservationHandler.php**
   - Loads reservation by ID
   - Calls `confirm()`
   - Saves and dispatches events

2. **CheckInGuestHandler.php**
   - Loads reservation
   - Calls `checkIn($roomNumber)`
   - Saves and dispatches events

3. **CheckOutGuestHandler.php**
   - Loads reservation
   - Calls `checkOut()`
   - Saves and dispatches events

4. **CancelReservationHandler.php**
   - Loads reservation
   - Calls `cancel($reason)`
   - Saves and dispatches events

5. **AddSpecialRequestHandler.php**
   - Loads reservation
   - Calls `addSpecialRequest(...)`
   - Saves and dispatches events

All handlers follow the same pattern: load → execute → save → dispatch events.

---

### PROMPT 20: Implement Integration Events

Create integration events in `src/BoundedContexts/Reservation/Application/IntegrationEvent/`:

1. **ReservationConfirmedEvent.php**
2. **ReservationCancelledEvent.php**
3. **GuestCheckedInEvent.php**
4. **GuestCheckedOutEvent.php**

Each implements `Shared\Infrastructure\Messaging\IntegrationEvent` and contains:
- Primitive type properties (string, int, array) - NO domain objects
- `readonly string $reservationId`
- `readonly DateTimeImmutable $occurredAt`
- `toArray(): array` method for serialization

Example for ReservationConfirmedEvent:
- reservationId, guestEmail, roomType, checkIn, checkOut, occurredAt

---

### PROMPT 21: Implement Integration Event Handlers

Create in `src/BoundedContexts/Reservation/Application/EventHandler/`:

These handlers listen to DOMAIN events and publish INTEGRATION events:

1. **OnReservationConfirmed.php**
   - Listens to: ReservationConfirmed domain event
   - Publishes: ReservationConfirmedEvent integration event
   - Maps domain event data to integration event DTO

2. **OnReservationCancelled.php**
3. **OnGuestCheckedIn.php**
4. **OnGuestCheckedOut.php**

Use Laravel's event listener pattern. Register in EventServiceProvider.

---

### PROMPT 22: Implement Eloquent Model

Create `src/BoundedContexts/Reservation/Infrastructure/Persistence/Eloquent/ReservationEloquent.php`:

- Table: `reservations`
- Casts:
  - status → ReservationStatus enum
  - special_requests → array (JSON)
  - check_in, check_out → DateTimeImmutable
  - All timestamps → DateTimeImmutable
- Relations: none (BCs are independent)
- Disable Laravel timestamps (use domain timestamps)

Also create the migration for the table with all necessary columns.

---

### PROMPT 23: Implement Repository Implementation

Create `src/BoundedContexts/Reservation/Infrastructure/Persistence/EloquentReservationRepository.php`:

Implements `ReservationRepository` interface.

- Method: `save(Reservation $reservation): void`
  - Maps domain Reservation → ReservationEloquent
  - Handles create vs update (check if exists)
  - Maps all VOs to primitive types
  - Serializes SpecialRequest entities to JSON

- Method: `findById(ReservationId $id): ?Reservation`
  - Loads ReservationEloquent
  - Reconstructs domain Reservation from data
  - Reconstructs all VOs and Entities
  - Returns null if not found

- Method: `findByGuestEmail(Email $email): array`
- Method: `nextIdentity(): ReservationId` - generates new UUID

Handle all mapping complexity. Use private methods for clarity.

---

### PROMPT 24: Implement ACL Adapter

Create `src/BoundedContexts/Reservation/Infrastructure/Integration/InventoryGatewayAdapter.php`:

Implements `InventoryGateway` interface.

For now, create a STUB implementation that:
- `checkAvailability()` returns hardcoded RoomAvailability (always 10 available)
- `getRoomTypeInfo()` returns hardcoded info for SINGLE, DOUBLE, SUITE

Add TODO comments indicating where to integrate with BC2's InventoryApi later.

---

### PROMPT 25: Implement Integration Event Publisher

Create `src/BoundedContexts/Reservation/Infrastructure/Messaging/IntegrationEventPublisher.php`:

Service that publishes integration events using Laravel Events.

- Method: `publish(IntegrationEvent $event): void`
- Uses Laravel's `Event::dispatch($event)`
- Add logging for debugging

Register as singleton in service provider.

---

### PROMPT 26: Implement Service Provider

Create `src/BoundedContexts/Reservation/ReservationServiceProvider.php`:

Register all bindings:
- `ReservationRepository::class → EloquentReservationRepository::class`
- `InventoryGateway::class → InventoryGatewayAdapter::class`
- Register command handlers
- Register domain event listeners → integration event publishers

Register in `bootstrap/providers.php`.

---

### PROMPT 27: Implement Controller

Create `src/BoundedContexts/Reservation/Presentation/Http/Controllers/ReservationController.php`:

Methods:
1. **store(CreateReservationRequest $request)** - POST /api/reservations
   - Validates request
   - Dispatches CreateReservation command
   - Returns 201 with ReservationResource

2. **show($id)** - GET /api/reservations/{id}
   - Loads reservation via repository
   - Returns ReservationResource

3. **confirm($id)** - POST /api/reservations/{id}/confirm
   - Dispatches ConfirmReservation command
   - Returns 200

4. **checkIn($id, CheckInRequest $request)** - POST /api/reservations/{id}/check-in
5. **checkOut($id)** - POST /api/reservations/{id}/check-out
6. **cancel($id, CancelRequest $request)** - POST /api/reservations/{id}/cancel

Use proper HTTP status codes and error handling.

---

### PROMPT 28: Implement Form Requests

Create validation requests in `src/BoundedContexts/Reservation/Presentation/Http/Requests/`:

1. **CreateReservationRequest.php**
   - Validate all guest fields
   - Validate dates (checkIn >= today, checkOut > checkIn)
   - Validate roomType enum

2. **CheckInRequest.php**
   - Validate roomNumber (required, string, regex pattern)

3. **CancelRequest.php**
   - Validate reason (required, string, min:10)

Use Laravel validation rules.

---

### PROMPT 29: Implement API Resource

Create `src/BoundedContexts/Reservation/Presentation/Http/Resources/ReservationResource.php`:

Transform domain Reservation to JSON:
```json
{
  "id": "uuid",
  "status": "confirmed",
  "guest": {
    "fullName": "...",
    "email": "...",
    "phone": "...",
    "isVip": false
  },
  "period": {
    "checkIn": "2024-03-01",
    "checkOut": "2024-03-05",
    "nights": 4
  },
  "roomType": "DOUBLE",
  "assignedRoomNumber": null,
  "specialRequests": [
    {
      "id": "uuid",
      "type": "EARLY_CHECK_IN",
      "description": "...",
      "status": "pending"
    }
  ],
  "timestamps": {
    "createdAt": "...",
    "confirmedAt": "..."
  }
}
```

---

### PROMPT 30: Create API Routes

Create `routes/api.php` (or separate file `routes/api/reservation.php`):

```php
Route::prefix('reservations')->group(function () {
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::post('/{id}/confirm', [ReservationController::class, 'confirm']);
    Route::post('/{id}/check-in', [ReservationController::class, 'checkIn']);
    Route::post('/{id}/check-out', [ReservationController::class, 'checkOut']);
    Route::post('/{id}/cancel', [ReservationController::class, 'cancel']);
    Route::post('/{id}/special-requests', [ReservationController::class, 'addSpecialRequest']);
});
```

Add middleware as needed (auth, api rate limiting).

---

### PROMPT 31: Create Database Migration

Create migration: `create_reservations_table.php`

Columns:
- id (uuid, primary)
- status (string)
- guest_full_name (string)
- guest_email (string)
- guest_phone (string)
- guest_document (string)
- guest_is_vip (boolean)
- check_in (date)
- check_out (date)
- room_type (string)
- assigned_room_number (nullable string)
- special_requests (json)
- cancellation_reason (nullable text)
- created_at (timestamp)
- confirmed_at (nullable timestamp)
- checked_in_at (nullable timestamp)
- checked_out_at (nullable timestamp)
- cancelled_at (nullable timestamp)

Add indexes for: guest_email, status, check_in, check_out.

---

### PROMPT 32: Create Configuration File

Create `config/bounded-contexts.php`:

```php
return [
    'reservation' => [
        'max_special_requests' => 5,
        'max_advance_booking_days' => [
            'regular' => 60,
            'vip' => 90,
        ],
        'min_stay_nights' => 1,
        'max_stay_nights' => 365,
    ],
    
    'inventory' => [
        'room_types' => ['SINGLE', 'DOUBLE', 'SUITE'],
    ],
];
```

Update ReservationPolicy and Reservation aggregate to use these configs.

---

### PROMPT 33: Implement Unit Tests for Value Objects

Create PHPUnit tests in `tests/Unit/BoundedContexts/Reservation/Domain/ValueObject/`:

1. **EmailTest.php** - test validation, equals, toString
2. **PhoneTest.php** - test format validation
3. **ReservationPeriodTest.php** - test nights calculation, overlaps, validations
4. **GuestTest.php** - test creation, equality

Use data providers for multiple test cases.

---

### PROMPT 34: Implement Unit Tests for Entities

Create tests in `tests/Unit/BoundedContexts/Reservation/Domain/`:

1. **SpecialRequestTest.php**
   - Test fulfill(), cancel(), state transitions
   - Test invalid state transitions throw exceptions

2. **ReservationTest.php**
   - Test state machine (pending → confirmed → checked-in → checked-out)
   - Test cancel logic
   - Test special request management
   - Test domain event recording
   - Test invalid transitions throw exceptions

Mock dependencies where needed.

---

### PROMPT 35: Implement Integration Tests

Create tests in `tests/Feature/BoundedContexts/Reservation/`:

1. **CreateReservationTest.php**
   - Test POST /api/reservations
   - Test validation errors
   - Test successful creation returns 201
   - Test database record created

2. **ReservationLifecycleTest.php**
   - Test full lifecycle: create → confirm → check-in → check-out
   - Test cancellation at different stages

Use Laravel's testing helpers (RefreshDatabase, actingAs, etc.).

---

### PROMPT 36: Create README Documentation

Create `src/BoundedContexts/Reservation/README.md`:

Document:
1. BC1 overview and responsibilities
2. Aggregate structure
3. API endpoints with examples
4. Event flows (domain + integration)
5. How to add new features
6. Testing guidelines

---

### PROMPT 37: Create Seeders

Create `database/seeders/ReservationSeeder.php`:

Seed sample data:
- 5 reservations in different states
- Include special requests
- Mix of VIP and regular guests
- Past, current, and future check-in dates

Useful for local development and testing.

---

## 📝 IMPLEMENTATION CHECKLIST

Track your progress:

- [ ] Prompt 1: Directory structure
- [ ] Prompt 2: Shared Kernel base classes
- [ ] Prompt 3-4: Email and Phone VOs
- [ ] Prompt 5: Enums
- [ ] Prompt 6-7: ReservationPeriod and Guest VOs
- [ ] Prompt 8: Identity VOs
- [ ] Prompt 9: Domain Events
- [ ] Prompt 10: SpecialRequest Entity
- [ ] Prompts 11-13: Reservation Aggregate
- [ ] Prompt 14: Domain Exceptions
- [ ] Prompt 15-16: Repository Interface, DTOs, Domain Service
- [ ] Prompts 17-21: Application Layer (Commands, Handlers, Integration Events)
- [ ] Prompts 22-25: Infrastructure Layer
- [ ] Prompt 26: Service Provider
- [ ] Prompts 27-30: Presentation Layer
- [ ] Prompt 31-32: Database and Configuration
- [ ] Prompts 33-35: Tests
- [ ] Prompts 36-37: Documentation and Seeders

---

## 🔍 KEY ARCHITECTURAL PRINCIPLES TO MAINTAIN

### 1. **Dependency Rule** (Clean Architecture)
```
Domain ← Application ← Infrastructure ← Presentation
```
- Domain depends on NOTHING
- Application depends only on Domain
- Infrastructure implements interfaces from Domain/Application
- Presentation depends on Application

### 2. **Persistence Ignorance**
- Domain objects never extend Eloquent Model
- No annotations/attributes on domain objects
- Repository pattern isolates persistence logic

### 3. **Aggregate Consistency**
- All changes to SpecialRequests go through Reservation
- One transaction = one aggregate instance
- External references use IDs (roomId), not objects

### 4. **Event Sourcing Readiness** (Optional)
- All state changes record domain events
- Events are immutable
- Can replay events to rebuild state

### 5. **Bounded Context Isolation**
- No direct database queries between BCs
- Communication via Integration Events (async) or ACL (sync)
- Each BC can have its own view of "Guest" if needed

---

## 🎓 TRADE-OFFS TO DISCUSS

### 1. **Modular Monolith vs Microservices**
**Choice**: Modular Monolith
- ✅ Simpler deployment
- ✅ Shared transactions (if needed)
- ✅ Lower operational complexity
- ❌ Harder to scale independently
- ❌ Risk of coupling over time

**When to Split**: If BCs have very different scaling needs or team ownership.

---

### 2. **Repository Mapping Complexity**
**Trade-off**: Rich domain model vs mapping overhead
- ✅ Domain remains pure and expressive
- ❌ More code to maintain (ORM ↔ Domain mapping)

**Alternative**: Anemic domain model (DTOs everywhere) - not recommended for complex business logic.

---

### 3. **CQRS Implementation**
**Current Choice**: Same model for reads and writes
- ✅ Simpler implementation
- ❌ Performance concerns for complex queries

**Future Enhancement**: Separate read models (projections) for analytics or complex queries.

---

### 4. **Event Bus Choice**
**Options**:
1. **Laravel Events** (current)
   - ✅ Simple, built-in
   - ❌ In-process only (no distribution)

2. **Redis Pub/Sub**
   - ✅ Distributed
   - ❌ No guaranteed delivery

3. **RabbitMQ/SQS**
   - ✅ Reliable, distributed
   - ❌ More complexity

**Start**: Laravel Events. Migrate to message broker when scaling requirements emerge.

---

## 🚨 COMMON PITFALLS TO AVOID

1. **Anemic Domain Model** - Putting business logic in services instead of entities
2. **Domain Events as DTOs** - Exposing domain events outside BC (use Integration Events)
3. **Bypassing Aggregate Root** - Directly modifying child entities
4. **Over-engineering** - Don't use DDD for simple CRUD operations
5. **Shared Database Between BCs** - Each BC should have schema/table isolation
6. **Direct Entity References Across BCs** - Use IDs, not object references

---

## 📚 NEXT STEPS AFTER BC1

1. **BC2: Inventory Context**
   - Implement Room aggregate
   - Create InventoryApi (called by BC1's adapter)
   - Subscribe to BC1's integration events

2. **Event Store** (optional)
   - Persist all domain events for audit/replay
   - Implement Event Sourcing for Reservation aggregate

3. **CQRS Read Models**
   - Create optimized projections for queries
   - Example: ReservationSummary (denormalized view)

4. **API Gateway**
   - If splitting to microservices, add API Gateway pattern
   - Handle cross-BC queries

5. **Saga Pattern**
   - For distributed transactions (e.g., reservation + payment)
   - Implement compensation logic

---

## 🎯 SUCCESS CRITERIA

Your implementation is successful when:

✅ Domain layer has zero framework dependencies  
✅ All state changes go through aggregate methods  
✅ Domain events are recorded on every state change  
✅ Integration events enable async BC communication  
✅ Repository pattern isolates persistence  
✅ Tests cover business rules (not just framework integration)  
✅ ACL protects BC1 from BC2's internal changes  
✅ Code is organized by capability (BC), not technical layer  

---

**Ready to implement?** Start with Prompt 1 and work sequentially. Each prompt builds on previous ones.

Good luck with your DDD implementation! 🚀
