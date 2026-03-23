# Stay & Billing — Domain Model Explanation

## Architecture Overview

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                            MODULAR MONOLITH                                              │
│                                                                                          │
│  ┌──────────────────────┐  ┌──────────────────────────┐  ┌──────────────────────────┐   │
│  │   IAM CONTEXT        │  │   STAY CONTEXT            │  │   BILLING CONTEXT        │   │
│  │                      │  │                           │  │                          │   │
│  │   Domain/            │  │   Domain/                 │  │   Domain/                │   │
│  │   ├── Actor (AR)     │  │   ├── Stay (AR)           │  │   ├── Invoice (AR)       │   │
│  │   ├── Account (AR)   │  │   ├── Reservation (AR)    │  │   │   ├── LineItem (E)   │   │
│  │   ├── User (AR)      │  │   │   └── SpecialReq (E)  │  │   │   └── Payment (E)    │   │
│  │   ├── Type (E)       │  │   ├── StayType (VO)       │  │   ├── InvoiceStatus (VO) │   │
│  │   ├── HashedPassword │  │   ├── StayCategory (VO)   │  │   ├── Money (VO)         │   │
│  │   │   (VO)           │  │   ├── ReservationPeriod   │  │   ├── PaymentStatus (VO) │   │
│  │   └── LoyaltyTier    │  │   │   (VO)                │  │   └── PaymentMethod (VO) │   │
│  │       (VO, nullable) │  │   └── ReservationStatus   │  │                          │   │
│  │                      │  │       (VO)                 │  │                          │   │
│  │   Domain events:     │  │                           │  │   Domain events:         │   │
│  │   ActorRegistered,   │  │   Domain events:          │  │   InvoiceCreated,        │   │
│  │   AccountCreated,    │  │   StayCreated,            │  │   InvoiceIssued,         │   │
│  │   UserCreated,       │  │   ReservationCreated,     │  │   InvoiceFullyPaid,      │   │
│  │   UserContactInfo-   │  │   ReservationConfirmed,   │  │   InvoiceVoided,         │   │
│  │   Updated,           │  │   GuestCheckedIn/Out,     │  │   InvoiceRefunded,       │   │
│  │   UserLoyaltyTier-   │  │   ReservationCancelled,   │  │   PaymentRecorded        │   │
│  │   Changed            │  │   SpecialRequestAdded/    │  │                          │   │
│  │                      │  │   Fulfilled               │  │                          │   │
│  └──────────┬───────────┘  └───────────▲──────┬────────┘  └──────────▲───────────────┘   │
│             │                          │      │                      │                    │
│             │  UserApi                 │      │  Integration Events  │                   │
│             │  (exposes user data)     │      │  (confirmed, checked │                   │
│             └──────────────────────────┘      │   out, cancelled)    │                   │
│                     GuestGateway              └──────────────────────┘                   │
│                     (reads user data)           Billing listens to                       │
│                                                 Stay events                              │
│                                                      │                                   │
│                                                      │ ReservationGateway                │
│                                                      │ (reads reservation data)          │
│                                                      ▼                                   │
│                                              Stay Eloquent models                        │
│                                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────────────────┐    │
│  │   SHARED KERNEL: AggregateRoot, Entity, ValueObject, Identity, DomainEvent,     │    │
│  │                  EventDispatcher, EventDispatchingHandler, IntegrationEvent,     │    │
│  │                  EventStore, Portal Views, Middleware, Seeders                   │    │
│  └──────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                          │
│  Integration events are dispatched via Laravel's event system, recorded in the event    │
│  store, and consumed by the Billing BC.                                                  │
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
| **Example** | SpecialRequest (can be fulfilled), Payment (can succeed/fail) | ReservationPeriod (replace entirely to update), Money (immutable amounts) |

---

# BC1: STAY CONTEXT

## Aggregate: Stay

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                 STAY AGGREGATE                                           │
│                                                                                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐ │
│  │                           Stay (Aggregate Root)                                    │ │
│  │                                                                                    │ │
│  │  Identity:                                                                         │ │
│  │  • uuid: StayId                                                                    │ │
│  │                                                                                    │ │
│  │  State:                                                                            │ │
│  │  • accountId: AccountId                     (tenant reference)                     │ │
│  │  • name: string                                                                    │ │
│  │  • slug: string                                                                    │ │
│  │  • description: ?string                                                            │ │
│  │  • address: ?string                                                                │ │
│  │  • type: StayType                           (room, entire_space)                   │ │
│  │  • category: StayCategory                   (hotel_room, house, apartment)         │ │
│  │  • pricePerNight: float                                                            │ │
│  │  • capacity: int                                                                   │ │
│  │  • contactEmail: ?string                                                           │ │
│  │  • contactPhone: ?string                                                           │ │
│  │  • status: string                           (active by default)                    │ │
│  │  • amenities: ?array                                                               │ │
│  │  • createdAt, updatedAt                                                            │ │
│  │                                                                                    │ │
│  │  Behavior:                                                                         │ │
│  │  • updateProfile(...): void                                                        │ │
│  │                                                                                    │ │
│  └───────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                         │
│  A Stay is an Airbnb-style property listing. It can be a room within a larger property  │
│  (hotel room) or an entire standalone space (house, apartment). Stays are owned by       │
│  accounts (tenants) and are the target of reservations.                                  │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

## Aggregate: Reservation

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              RESERVATION AGGREGATE                                       │
│                                                                                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐ │
│  │                      Reservation (Aggregate Root)                                  │ │
│  │                                                                                    │ │
│  │  Identity:                                                                         │ │
│  │  • uuid: ReservationId                                                             │ │
│  │                                                                                    │ │
│  │  State:                                                                            │ │
│  │  • guestId: string                        (soft link to IAM User)                  │ │
│  │  • accountId: string                      (tenant reference)                       │ │
│  │  • stayId: string                         (soft link to Stay)                      │ │
│  │  • status: ReservationStatus                                                       │ │
│  │  • createdAt, confirmedAt, checkedInAt, checkedOutAt, cancelledAt                  │ │
│  │  • cancellationReason: ?string                                                     │ │
│  │                                                                                    │ │
│  │  Compositions:                                                                     │ │
│  │  • period: ReservationPeriod (VO)                                                  │ │
│  │  • specialRequests: SpecialRequest[] (Entities)  <-- ENTITIES with own identity    │ │
│  │                                                                                    │ │
│  │  Behavior:                                                                         │ │
│  │  • confirm(): void                                                                 │ │
│  │  • checkIn(): void                                                                 │ │
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
│  │ • checkIn: Date         │    │ • id: SpecialRequestId  <--- │                       │
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
decoupled from the IAM BC.
```

### Why SpecialRequest is an ENTITY (not VO):

1. **Has Identity** — Each request has a `SpecialRequestId`, allowing you to reference it: "fulfill request X"
2. **Changes State** — Request can be fulfilled, cancelled, or description updated
3. **Tracked Over Time** — You need to know when it was created, when fulfilled
4. **Individual Operations** — You can remove or fulfill a specific request by ID

---

# BC2: BILLING CONTEXT

## Aggregate: Invoice

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                INVOICE AGGREGATE                                         │
│                                                                                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐ │
│  │                         Invoice (Aggregate Root)                                   │ │
│  │                                                                                    │ │
│  │  Identity:                                                                         │ │
│  │  • uuid: InvoiceId                                                                 │ │
│  │                                                                                    │ │
│  │  State:                                                                            │ │
│  │  • accountId: string                      (tenant reference)                       │ │
│  │  • reservationId: string                  (soft link to Stay Reservation)          │ │
│  │  • guestId: string                        (soft link to IAM User)                  │ │
│  │  • status: InvoiceStatus                  (draft, issued, paid, void, refunded)    │ │
│  │  • subtotal: Money                        (sum of line item totals)                │ │
│  │  • tax: Money                             (calculated from tax rate)               │ │
│  │  • total: Money                           (subtotal + tax)                         │ │
│  │  • stripeCustomerId: ?string                                                      │ │
│  │  • notes: ?string                                                                  │ │
│  │  • createdAt, issuedAt, paidAt, voidedAt, refundedAt                              │ │
│  │                                                                                    │ │
│  │  Compositions:                                                                     │ │
│  │  • lineItems: LineItem[] (Entities)                                                │ │
│  │  • payments: Payment[] (Entities)                                                  │ │
│  │                                                                                    │ │
│  │  Behavior:                                                                         │ │
│  │  • issue(): void                                                                   │ │
│  │  • recordPayment(id, amount, method, stripeId, createdAt): void                   │ │
│  │  • markPaymentSucceeded(stripePaymentIntentId): void                              │ │
│  │  • markPaymentFailed(stripePaymentIntentId, reason): void                         │ │
│  │  • void(reason): void                                                              │ │
│  │  • refund(): void                                                                  │ │
│  │  • setStripeCustomerId(id): void                                                   │ │
│  │                                                                                    │ │
│  └───────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                         │
│         │ contains                                    │ contains                        │
│         ▼                                             ▼                                 │
│                                                                                         │
│  ┌─────────────────────────┐    ┌──────────────────────────────┐                       │
│  │     LineItem            │    │       Payment                │                       │
│  │      (Entity)           │    │        (Entity)              │                       │
│  │                         │    │                              │                       │
│  │ • id: LineItemId        │    │ • id: PaymentId              │                       │
│  │ • description: string   │    │ • amount: Money              │                       │
│  │ • unitPrice: Money      │    │ • status: PaymentStatus      │                       │
│  │ • quantity: int         │    │ • method: PaymentMethod      │                       │
│  │ • total: Money          │    │ • stripePaymentIntentId:     │                       │
│  │                         │    │   ?string                    │                       │
│  │ Immutable after         │    │ • createdAt, succeededAt,    │                       │
│  │ creation                │    │   failedAt, failureReason    │                       │
│  │                         │    │                              │                       │
│  └─────────────────────────┘    │ Mutable:                     │                       │
│                                 │ • markSucceeded(): void      │                       │
│                                 │ • markFailed(reason): void   │                       │
│                                 │ • markRefunded(): void       │                       │
│                                 │                              │                       │
│                                 │ Lifecycle tied to Invoice    │                       │
│                                 └──────────────────────────────┘                       │
│                                                                                         │
│  Money is a Value Object with amountInCents (int) and currency (string).               │
│  Operations: add(), multiply(), zero(). Amounts are stored in cents to avoid            │
│  floating-point precision issues.                                                       │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

### Why LineItem and Payment are ENTITIES:

**LineItem:**
1. **Has Identity** — Each line item has a `LineItemId`
2. **Part of Aggregate** — Cannot exist without an Invoice
3. **Distinguishable** — Multiple line items can describe the same service at different prices

**Payment:**
1. **Has Identity** — Each payment has a `PaymentId`
2. **Changes State** — Payment transitions through pending → succeeded/failed
3. **Tracked Over Time** — Records when it was created, succeeded, or failed
4. **External Reference** — Links to Stripe via `stripePaymentIntentId`

---

## Domain Layer Structure (Stay BC)

```
Stay/
└── Domain/
    ├── Stay.php                           # Aggregate Root
    ├── StayId.php                         # Identity VO
    ├── Reservation.php                    # Aggregate Root
    ├── ReservationId.php                  # Identity VO
    ├── SpecialRequest.php                 # Child Entity (has identity, mutable)
    │
    ├── ValueObject/
    │   ├── StayType.php                   # Enum (room, entire_space)
    │   ├── StayCategory.php               # Enum (hotel_room, house, apartment)
    │   ├── ReservationPeriod.php          # VO
    │   ├── SpecialRequestId.php           # Identity VO for child entity
    │   ├── ReservationStatus.php          # Enum
    │   ├── RequestType.php                # Enum
    │   └── RequestStatus.php              # Enum (pending, fulfilled, cancelled)
    │
    ├── Dto/                               # DTOs for cross-BC data
    │   └── GuestInfo.php                  # Guest data fetched via GuestGateway
    │
    ├── Event/                             # DOMAIN EVENTS (internal to BC)
    │   ├── StayCreated.php
    │   ├── ReservationCreated.php
    │   ├── ReservationConfirmed.php
    │   ├── GuestCheckedIn.php
    │   ├── GuestCheckedOut.php
    │   ├── ReservationCancelled.php
    │   ├── SpecialRequestAdded.php
    │   └── SpecialRequestFulfilled.php
    │
    ├── Repository/
    │   ├── StayRepository.php             # Interface
    │   └── ReservationRepository.php      # Interface
    │
    ├── Service/                           # Domain Service Interfaces (ports)
    │   └── GuestGateway.php               # Port for fetching user data from IAM BC
    │
    ├── Specification/
    │   └── ReservationCreationSpecification.php
    │
    └── Exception/
        ├── StayNotFoundException.php
        ├── ReservationNotFoundException.php
        ├── InvalidReservationStateException.php
        └── MaxSpecialRequestsExceededException.php
```

## Domain Layer Structure (Billing BC)

```
Billing/
└── Domain/
    ├── Invoice.php                        # Aggregate Root
    ├── InvoiceId.php                      # Identity VO
    ├── LineItem.php                       # Child Entity
    ├── LineItemId.php                     # Identity VO
    ├── Payment.php                        # Child Entity
    ├── PaymentId.php                      # Identity VO
    │
    ├── ValueObject/
    │   ├── Money.php                      # VO (amountInCents, currency)
    │   ├── InvoiceStatus.php              # Enum (draft, issued, paid, void, refunded)
    │   ├── PaymentStatus.php              # Enum (pending, succeeded, failed, refunded)
    │   └── PaymentMethod.php              # Enum (card, bank_transfer, other)
    │
    ├── Event/                             # DOMAIN EVENTS (internal to BC)
    │   ├── InvoiceCreated.php
    │   ├── InvoiceIssued.php
    │   ├── InvoiceFullyPaid.php
    │   ├── InvoiceVoided.php
    │   ├── InvoiceRefunded.php
    │   └── PaymentRecorded.php
    │
    ├── Repository/
    │   └── InvoiceRepository.php          # Interface
    │
    ├── Service/                           # Domain Service Interfaces (ports)
    │   ├── ReservationGateway.php         # Port for reading reservation data from Stay BC
    │   ├── ReservationInfo.php            # DTO returned by ReservationGateway
    │   ├── PaymentGateway.php             # Port for Stripe payment processing
    │   └── PaymentGatewayResult.php       # DTO returned by PaymentGateway
    │
    └── Exception/
        ├── InvalidInvoiceStateException.php
        ├── InvoiceNotFoundException.php
        └── PaymentNotFoundException.php
```

---

## Domain Events

Domain events are recorded inside aggregates via `recordEvent()` and dispatched by command handlers after persistence.

### Stay Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `StayCreated` | `Stay::create()` | `stayId`, `name` |
| `ReservationCreated` | `Reservation::create()` | `reservationId` |
| `ReservationConfirmed` | `confirm()` | `reservationId` |
| `ReservationCancelled` | `cancel()` | `reservationId`, `reason` |
| `GuestCheckedIn` | `checkIn()` | `reservationId` |
| `GuestCheckedOut` | `checkOut()` | `reservationId` |
| `SpecialRequestAdded` | `addSpecialRequest()` | `reservationId`, `requestId` |
| `SpecialRequestFulfilled` | `fulfillSpecialRequest()` | `reservationId`, `requestId` |

### Billing Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `InvoiceCreated` | `Invoice::createForReservation()` | `invoiceId`, `reservationId` |
| `InvoiceIssued` | `issue()` | `invoiceId` |
| `InvoiceFullyPaid` | `markPaymentSucceeded()` (total covered) | `invoiceId`, `reservationId` |
| `InvoiceVoided` | `void()` | `invoiceId`, `reason` |
| `InvoiceRefunded` | `refund()` | `invoiceId` |
| `PaymentRecorded` | `recordPayment()` | `invoiceId`, `paymentId` |

---

## Integration Events

Integration events are enriched, serializable versions of domain events for cross-BC or external consumption. They carry all context needed by consumers (no further lookups required). All implement `IntegrationEvent` (with `occurredAt()` and `toArray()`).

| Event | Source Domain Event | Extra Data |
|-------|-------------------|------------|
| `ReservationConfirmedEvent` | `ReservationConfirmed` | guestEmail, stayId, checkIn, checkOut, isVip |
| `ReservationCancelledEvent` | `ReservationCancelled` | stayId, checkIn, checkOut, reason |
| `GuestCheckedInEvent` | `GuestCheckedIn` | guestEmail, isVip |
| `GuestCheckedOutEvent` | `GuestCheckedOut` | guestEmail |

The Billing BC consumes these integration events to automate invoice creation and management.

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
│   │ • Used within Stay BC       │   ───►     │ • Dispatched via Laravel    │           │
│   │   for side effects          │  mapped    │   event system              │           │
│   │ • Triggers internal         │    to      │ • Consumed by Billing BC    │           │
│   │   handlers                  │            │ • Contains only data other  │           │
│   │ • Rich domain object refs   │            │   BCs need (no domain refs) │           │
│   └─────────────────────────────┘            └─────────────────────────────┘           │
│                                                                                         │
│   WHERE THEY LIVE:                           WHERE THEY LIVE:                          │
│   Domain/Event/                              Infrastructure/IntegrationEvent/           │
│                                                                                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

### Flow: Domain Event → Integration Event → Cross-BC Consumer

```
┌──────────────┐     ┌──────────────────┐     ┌─────────────────────┐     ┌──────────────────┐
│              │     │                  │     │                     │     │                  │
│ Reservation  │────►│  Domain Event    │────►│  Stay Application   │────►│  Integration     │
│ .confirm()   │     │  Raised          │     │  Listener maps to   │     │  Event Dispatched│
│              │     │                  │     │  Integration Event  │     │  (via Laravel)   │
└──────────────┘     └──────────────────┘     └─────────────────────┘     └──────────────────┘
                                                                                  │
                                                                    ┌─────────────┴──────────┐
                                                                    │                        │
                                                              ┌─────▼──────────┐   ┌────────▼─────────┐
                                                              │ IntegrationEvent│   │ Billing BC       │
                                                              │ Publisher       │   │ OnReservation-   │
                                                              │ (logs + stores) │   │ Confirmed        │
                                                              └────────────────┘   │ (creates invoice)│
                                                                                    └──────────────────┘
```

---

# CROSS-BC INTEGRATION

The system has three active integration paths:

| From | To | Gateway (Port) | Adapter (ACL) | Mechanism |
|------|----|----------------|---------------|-----------|
| Stay | IAM | `GuestGateway` | `GuestGatewayAdapter` → `UserApi` | Sync (direct call) |
| IAM | IAM (User) | `UserGateway` | `UserGatewayAdapter` → `UserApi` | Sync (direct call) |
| Billing | Stay | `ReservationGateway` | `ReservationGatewayAdapter` → Stay Eloquent models | Sync (direct call) |
| Billing | Stripe | `PaymentGateway` | `StripePaymentGateway` | Async (webhooks) |
| Stay | Billing | Integration Events | Laravel event system | Async-style (listener) |

## Stay → IAM (User Data)

Stay needs user data (name, email, VIP status) to enrich integration events. The domain defines a port; the infrastructure adapter calls IAM's exposed `UserApi`.

```php
// Stay/Domain/Service/GuestGateway.php (PORT)
interface GuestGateway
{
    public function findByUuid(string $guestId): ?GuestInfo;
}

// Stay/Domain/Dto/GuestInfo.php
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

// Stay/Infrastructure/Integration/GuestGatewayAdapter.php (ACL)
// Calls IAM's UserApi and translates to Stay's own DTO
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

## Billing → Stay (Reservation Data)

Billing needs reservation and stay details for invoice creation. The domain defines a port; the adapter queries Stay's Eloquent models directly (pragmatic read access).

```php
// Billing/Domain/Service/ReservationGateway.php (PORT)
interface ReservationGateway
{
    public function findReservation(string $reservationId): ?ReservationInfo;
}

// Billing/Domain/Service/ReservationInfo.php (DTO)
final readonly class ReservationInfo
{
    public function __construct(
        public string $reservationId,
        public string $guestId,
        public string $stayId,
        public string $stayName,
        public string $accountId,
        public string $checkIn,
        public string $checkOut,
        public int $nights,
        public float $pricePerNight,
    ) {}
}
```

## Billing → Stripe (Payment Processing)

```php
// Billing/Domain/Service/PaymentGateway.php (PORT)
interface PaymentGateway { ... }

// Billing/Infrastructure/Stripe/StripePaymentGateway.php (ADAPTER)
// Handles creating payment intents, processing webhooks

// Billing/Infrastructure/Stripe/StripeWebhookController.php
// Receives Stripe webhook callbacks for payment.succeeded / payment.failed
```

## IAM — Internal User Management

IAM creates user profiles when actors register, using an internal gateway:

```php
// IAM/Domain/Service/UserGateway.php (PORT)
interface UserGateway
{
    public function create(string $name, string $email, string $phone, string $document, ?string $loyaltyTier = null): int;
}

// IAM/Infrastructure/Integration/UserApi.php (EXPOSED API)
// Entry point for cross-BC access to user data
final readonly class UserApi
{
    public function create(string $name, string $email, ...): int;
    public function findByUuid(string $uuid): ?UserData;
}
```

## Stay — Exposed Integration API

```php
// Stay/Infrastructure/Integration/StayApi.php (EXPOSED API)
final class StayApi
{
    public function findByUuid(string $uuid): ?StayData;
    public function isAvailable(string $uuid): bool;
}
```

---

# COMPLETE FOLDER STRUCTURE

```
src/modules/
├── Stay/                                     # BC: Stay (properties + reservations)
│   │
│   ├── Domain/
│   │   ├── Stay.php                          # Aggregate Root
│   │   ├── StayId.php                        # Identity VO
│   │   ├── Reservation.php                   # Aggregate Root
│   │   ├── ReservationId.php                 # Identity VO
│   │   ├── SpecialRequest.php                # Child Entity (has identity, mutable)
│   │   │
│   │   ├── ValueObject/
│   │   │   ├── StayType.php                  # Enum (room, entire_space)
│   │   │   ├── StayCategory.php              # Enum (hotel_room, house, apartment)
│   │   │   ├── ReservationPeriod.php
│   │   │   ├── SpecialRequestId.php
│   │   │   ├── ReservationStatus.php         # Enum
│   │   │   ├── RequestType.php               # Enum
│   │   │   └── RequestStatus.php             # Enum (pending, fulfilled, cancelled)
│   │   │
│   │   ├── Dto/
│   │   │   └── GuestInfo.php                 # Guest data fetched via GuestGateway
│   │   │
│   │   ├── Event/
│   │   │   ├── StayCreated.php
│   │   │   ├── ReservationCreated.php
│   │   │   ├── ReservationConfirmed.php
│   │   │   ├── GuestCheckedIn.php
│   │   │   ├── GuestCheckedOut.php
│   │   │   ├── ReservationCancelled.php
│   │   │   ├── SpecialRequestAdded.php
│   │   │   └── SpecialRequestFulfilled.php
│   │   │
│   │   ├── Repository/
│   │   │   ├── StayRepository.php            # Interface
│   │   │   └── ReservationRepository.php     # Interface
│   │   │
│   │   ├── Service/
│   │   │   └── GuestGateway.php              # Port for IAM user data
│   │   │
│   │   ├── Specification/
│   │   │   └── ReservationCreationSpecification.php
│   │   │
│   │   └── Exception/
│   │       ├── StayNotFoundException.php
│   │       ├── ReservationNotFoundException.php
│   │       ├── InvalidReservationStateException.php
│   │       └── MaxSpecialRequestsExceededException.php
│   │
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateStay.php
│   │   │   ├── CreateStayHandler.php
│   │   │   ├── UpdateStay.php
│   │   │   ├── UpdateStayHandler.php
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
│   │   ├── Listeners/                        # Domain event → Integration event
│   │   │   ├── OnReservationCreated.php
│   │   │   ├── OnReservationConfirmed.php
│   │   │   ├── OnReservationCancelled.php
│   │   │   ├── OnGuestCheckedIn.php
│   │   │   └── OnGuestCheckedOut.php
│   │   │
│   │   └── Query/
│   │       ├── ListStays.php
│   │       ├── ListStaysHandler.php
│   │       ├── GetStayStats.php
│   │       ├── GetStayStatsHandler.php
│   │       ├── StayStatsResult.php
│   │       ├── ListReservations.php
│   │       ├── ListReservationsHandler.php
│   │       ├── GetReservation.php
│   │       ├── GetReservationHandler.php
│   │       ├── ReservationReadModel.php
│   │       ├── GetReservationStats.php
│   │       ├── GetReservationStatsHandler.php
│   │       └── ReservationStatsResult.php
│   │
│   ├── Presentation/
│   │   └── Http/
│   │       ├── Action/                       # PSR-7 API actions
│   │       └── Presenter/
│   │           └── StayPresenter.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── StayReflector.php
│       │   ├── ReservationReflector.php
│       │   ├── SpecialRequestReflector.php
│       │   ├── Eloquent/
│       │   │   ├── StayModel.php
│       │   │   ├── ReservationModel.php
│       │   │   ├── EloquentStayRepository.php
│       │   │   └── EloquentReservationRepository.php
│       │   ├── Migrations/
│       │   └── Seeders/
│       │       ├── StaySeeder.php
│       │       └── ReservationSeeder.php
│       │
│       ├── Integration/
│       │   ├── StayApi.php                   # Exposed API for other BCs
│       │   ├── Dto/
│       │   │   └── StayData.php
│       │   └── GuestGatewayAdapter.php       # Implements GuestGateway (calls IAM UserApi)
│       │
│       ├── IntegrationEvent/
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
│       ├── Config/
│       │   └── reservation.php
│       │
│       ├── Routes/
│       │   ├── api.php
│       │   └── web.php
│       │
│       └── Providers/
│           └── StayServiceProvider.php
│
├── Billing/                                  # BC: Billing (invoices + payments + Stripe)
│   │
│   ├── Domain/
│   │   ├── Invoice.php                       # Aggregate Root
│   │   ├── InvoiceId.php
│   │   ├── LineItem.php                      # Child Entity
│   │   ├── LineItemId.php
│   │   ├── Payment.php                       # Child Entity
│   │   ├── PaymentId.php
│   │   │
│   │   ├── ValueObject/
│   │   │   ├── Money.php
│   │   │   ├── InvoiceStatus.php
│   │   │   ├── PaymentStatus.php
│   │   │   └── PaymentMethod.php
│   │   │
│   │   ├── Event/
│   │   │   ├── InvoiceCreated.php
│   │   │   ├── InvoiceIssued.php
│   │   │   ├── InvoiceFullyPaid.php
│   │   │   ├── InvoiceVoided.php
│   │   │   ├── InvoiceRefunded.php
│   │   │   └── PaymentRecorded.php
│   │   │
│   │   ├── Repository/
│   │   │   └── InvoiceRepository.php
│   │   │
│   │   ├── Service/
│   │   │   ├── ReservationGateway.php
│   │   │   ├── ReservationInfo.php
│   │   │   ├── PaymentGateway.php
│   │   │   └── PaymentGatewayResult.php
│   │   │
│   │   └── Exception/
│   │       ├── InvalidInvoiceStateException.php
│   │       ├── InvoiceNotFoundException.php
│   │       └── PaymentNotFoundException.php
│   │
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateInvoiceForReservation.php
│   │   │   ├── CreateInvoiceForReservationHandler.php
│   │   │   ├── IssueInvoice.php
│   │   │   ├── IssueInvoiceHandler.php
│   │   │   ├── InitiatePayment.php
│   │   │   ├── InitiatePaymentHandler.php
│   │   │   ├── HandlePaymentSucceeded.php
│   │   │   ├── HandlePaymentSucceededHandler.php
│   │   │   ├── HandlePaymentFailed.php
│   │   │   ├── HandlePaymentFailedHandler.php
│   │   │   ├── VoidInvoice.php
│   │   │   ├── VoidInvoiceHandler.php
│   │   │   ├── RefundInvoice.php
│   │   │   └── RefundInvoiceHandler.php
│   │   │
│   │   ├── Listeners/                        # Integration event consumers
│   │   │   ├── OnReservationConfirmed.php    # Creates invoice on confirmation
│   │   │   ├── OnGuestCheckedOut.php         # Post-checkout billing
│   │   │   └── OnReservationCancelled.php    # Voids invoice on cancellation
│   │   │
│   │   └── Query/
│   │       ├── InvoiceReadModel.php
│   │       ├── GetBillingStats.php
│   │       ├── GetBillingStatsHandler.php
│   │       └── BillingStatsResult.php
│   │
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── InvoiceReflector.php
│       │   ├── Eloquent/
│       │   │   ├── InvoiceModel.php
│       │   │   ├── LineItemModel.php
│       │   │   ├── PaymentModel.php
│       │   │   ├── StripeWebhookEventModel.php
│       │   │   └── EloquentInvoiceRepository.php
│       │   ├── Migrations/
│       │   └── Seeders/
│       │       └── InvoiceSeeder.php
│       │
│       ├── Integration/
│       │   └── ReservationGatewayAdapter.php  # Reads Stay Eloquent models
│       │
│       ├── Stripe/
│       │   ├── StripePaymentGateway.php      # PaymentGateway adapter
│       │   └── StripeWebhookController.php   # Webhook handler
│       │
│       ├── Http/
│       │   └── View/                         # Inertia view classes + actions
│       │
│       ├── Config/
│       │   └── billing.php
│       │
│       ├── Routes/
│       │   ├── api.php
│       │   └── web.php
│       │
│       └── Providers/
│           └── BillingServiceProvider.php
│
├── IAM/                                      # BC: Identity & Access Management
│   │
│   ├── Domain/
│   │   ├── Actor.php                         # Aggregate Root
│   │   ├── ActorId.php
│   │   ├── Account.php                       # Aggregate Root
│   │   ├── AccountId.php
│   │   ├── User.php                          # Aggregate Root
│   │   ├── UserId.php
│   │   ├── Type.php                          # Entity
│   │   ├── TypeId.php
│   │   │
│   │   ├── ValueObject/
│   │   │   ├── TypeName.php                  # Enum (SUPERADMIN, OWNER, GUEST)
│   │   │   ├── HashedPassword.php
│   │   │   └── LoyaltyTier.php              # Enum (BRONZE, SILVER, GOLD, PLATINUM)
│   │   │
│   │   ├── Event/
│   │   │   ├── ActorRegistered.php
│   │   │   ├── AccountCreated.php
│   │   │   ├── UserCreated.php
│   │   │   ├── UserContactInfoUpdated.php
│   │   │   └── UserLoyaltyTierChanged.php
│   │   │
│   │   ├── Repository/
│   │   │   ├── ActorRepository.php
│   │   │   ├── AccountRepository.php
│   │   │   ├── TypeRepository.php
│   │   │   └── UserRepository.php
│   │   │
│   │   ├── Service/
│   │   │   ├── PasswordHasher.php
│   │   │   ├── TokenManager.php
│   │   │   ├── UserGateway.php
│   │   │   └── EmailUniquenessChecker.php
│   │   │
│   │   └── Exception/
│   │       ├── ActorAlreadyExistsException.php
│   │       ├── ActorNotFoundException.php
│   │       ├── InvalidCredentialsException.php
│   │       └── UserNotFoundException.php
│   │
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── RegisterActor.php
│   │   │   ├── RegisterActorHandler.php
│   │   │   ├── RegisterHotelOwner.php
│   │   │   ├── RegisterHotelOwnerHandler.php
│   │   │   ├── AuthenticateActor.php
│   │   │   ├── AuthenticateActorHandler.php
│   │   │   ├── RevokeToken.php
│   │   │   ├── RevokeTokenHandler.php
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
│   └── Infrastructure/
│       ├── Persistence/
│       │   ├── ActorReflector.php
│       │   ├── AccountReflector.php
│       │   ├── UserReflector.php
│       │   ├── Eloquent/
│       │   │   ├── ActorModel.php
│       │   │   ├── AccountModel.php
│       │   │   ├── ActorTypeModel.php
│       │   │   ├── UserModel.php
│       │   │   ├── EloquentActorRepository.php
│       │   │   ├── EloquentAccountRepository.php
│       │   │   ├── EloquentActorTypeRepository.php
│       │   │   ├── EloquentUserRepository.php
│       │   │   └── EloquentEmailUniquenessChecker.php
│       │   ├── Migrations/
│       │   └── Seeders/
│       │       ├── ActorSeeder.php
│       │       ├── ActorTypeSeeder.php
│       │       ├── AccountSeeder.php
│       │       └── UserSeeder.php
│       │
│       ├── Integration/
│       │   ├── UserApi.php                   # Exposed API for other BCs
│       │   ├── Dto/
│       │   │   └── UserData.php
│       │   └── UserGatewayAdapter.php
│       │
│       ├── Services/
│       │   ├── BcryptPasswordHasher.php
│       │   └── SanctumTokenManager.php
│       │
│       ├── Http/
│       │   ├── Action/                       # HTTP actions (login, register, CRUD)
│       │   ├── View/                         # Inertia view classes
│       │   └── Presenter/
│       │       ├── ActorPresenter.php
│       │       └── UserPresenter.php
│       │
│       ├── Routes/
│       │   ├── api.php
│       │   └── web.php
│       │
│       └── Providers/
│           └── IAMServiceProvider.php
│
└── Shared/                                   # Shared Kernel + Portal + Middleware
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
    │   ├── EventStore.php
    │   ├── StoredEvent.php
    │   ├── Query/
    │   │   └── Pagination.php
    │   └── Messaging/
    │       └── IntegrationEvent.php
    │
    ├── Presentation/
    │   ├── Http/
    │   │   └── JsonResponder.php
    │   ├── Validation/
    │   │   └── InputValidator.php
    │   └── Exception/
    │       └── InputValidationException.php
    │
    └── Infrastructure/
        ├── Persistence/
        │   ├── TenantContext.php
        │   ├── Eloquent/
        │   │   ├── BelongsToTenant.php
        │   │   ├── EloquentEventStore.php
        │   │   └── StoredEventModel.php
        │   ├── Migrations/
        │   └── Seeders/
        │       └── MassSeeder.php
        │
        ├── Http/
        │   ├── Middleware/
        │   │   ├── HandleInertiaRequests.php
        │   │   ├── EnsureActorType.php
        │   │   ├── EnsureActorIsOwner.php
        │   │   ├── EnsureActorIsGuest.php
        │   │   ├── SetTenantContext.php
        │   │   └── MapRouteParameters.php
        │   └── View/
        │       ├── DashboardView.php
        │       ├── LandingPageView.php
        │       └── Portal/
        │           ├── PortalDashboardView.php
        │           ├── PortalStayListView.php
        │           ├── PortalStayShowView.php
        │           ├── PortalReservationsView.php
        │           ├── PortalReservationShowView.php
        │           ├── PortalReservationStoreView.php
        │           ├── PortalCancelReservationView.php
        │           ├── PortalAddSpecialRequestView.php
        │           ├── PortalProfileView.php
        │           ├── PortalProfileEditView.php
        │           ├── PortalProfileUpdateView.php
        │           ├── PortalInvoiceListView.php
        │           ├── PortalInvoiceShowView.php
        │           └── PortalInitiatePaymentAction.php
        │
        ├── Validation/
        │   └── LaravelInputValidator.php
        │
        ├── Service/
        │   └── AuthenticatedUserResolver.php
        │
        ├── Messaging/
        │   ├── LaravelEventDispatcher.php
        │   └── EventStoreRecorder.php
        │
        ├── Routes/
        │   └── portal.php
        │
        └── Providers/
            ├── EventStoreServiceProvider.php
            └── PsrHttpServiceProvider.php
```

---

# SUMMARY

| Aspect | Stay | Billing | IAM | Shared |
|--------|------|---------|-----|--------|
| **Aggregate Roots** | Stay, Reservation | Invoice | Actor, Account, User | — |
| **Child Entities** | SpecialRequest | LineItem, Payment | Type | — |
| **Value Objects** | StayType, StayCategory, ReservationPeriod, ReservationStatus, RequestType, RequestStatus, SpecialRequestId | Money, InvoiceStatus, PaymentStatus, PaymentMethod | TypeName, HashedPassword, LoyaltyTier (nullable) | — |
| **DTOs** | GuestInfo, StayData (integration) | ReservationInfo, PaymentGatewayResult, InvoiceReadModel, BillingStatsResult | UserData (integration), UserStatsResult | PaginatedResult, Pagination |
| **Domain Events** | 8 (internal) | 6 (internal) | 5 | — |
| **Integration Events** | 4 (published, consumed by Billing) | — | — | — |
| **Cross-BC Ports** | GuestGateway | ReservationGateway, PaymentGateway | UserGateway, EmailUniquenessChecker | EventDispatcher, EventStore |
