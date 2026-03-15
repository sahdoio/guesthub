# Event Storming — GuestHub

> Color map based on Event Storming notation.
>
> Reference: [Remote Event Storming Workshop — DDD Practitioners](https://ddd-practitioners.com/2023/03/20/remote-eventstorming-workshop/)

| Color | Element | Role |
|-------|---------|------|
| 🟧 Orange | **Event** | Something that happened in the domain (past tense) |
| 🟦 Blue | **Command** | Intent to cause an event |
| 🟨 Yellow | **Actor** | Who triggers the command |
| 🟪 Purple | **Policy** | Reactive rule ("whenever X, then Y") |
| 🟩 Green | **Read Model** | Data projection for decision-making |
| 🟥 Red | **Test** | Acceptance criteria |
| ⬜ Gray | **Question** | Doubts or uncertainties |
| ◼️ Dark | **Invariant** | Rules that can never be violated |

---

## Bounded Context: IAM (Identity & Access Management)

### Flow: Actor Registration (Guest Self-Registration)

🟨 **Actor:** Visitor (anonymous user)

🟩 **Read Model:** Registration form (name, email, password, phone, document)

🟦 **Command:** Register Actor
> `accountName, name, email, password, phone, document`

◼️ **Invariant:** Email must be unique across the system
◼️ **Invariant:** Document must be unique across the system
◼️ **Invariant:** Password must be valid (hashed via bcrypt)

🟧 **Event:** Account Created
> `accountId, name`

🟧 **Event:** Actor Registered
> `actorId, accountId, email, role=GUEST`

🟪 **Policy:** Whenever Actor Registered (role=GUEST), then Create Guest
> Integration via GuestGateway

🟧 **Event:** Guest Created
> `guestId, email, loyaltyTier=BRONZE`

🟪 **Policy:** Whenever Guest Created, then Link Actor to Guest
> `Actor.subjectType='guest', Actor.subjectId=guestId`

---

### Flow: Authentication (Login)

🟨 **Actor:** Visitor (anonymous user)

🟩 **Read Model:** Login form (email, password)

🟦 **Command:** Authenticate Actor
> `email, password`

◼️ **Invariant:** Email must exist in the system
◼️ **Invariant:** Password must match the stored hash

🟧 **Event:** Actor Authenticated
> `actorId, token (Sanctum)`

🟥 **Test:** Login with valid credentials returns token
🟥 **Test:** Login with invalid credentials returns 401 error

---

### Flow: Logout

🟨 **Actor:** Guest | Admin | SuperAdmin

🟦 **Command:** Revoke Token

🟧 **Event:** Token Revoked
> `actorId`

---

### ⬜ Questions — IAM

- How does password recovery work?
- Is there an email verification flow?
- Can Admins be created via API or only via seeder?

---

## Bounded Context: Guest (Guest Management)

### Flow: Guest Creation (via admin API)

🟨 **Actor:** Admin | SuperAdmin

🟩 **Read Model:** Existing guest list

🟦 **Command:** Create Guest
> `fullName, email, phone, document`

◼️ **Invariant:** Document must be unique

🟧 **Event:** Guest Created
> `guestId, email, loyaltyTier=BRONZE`

---

### Flow: Guest Update

🟨 **Actor:** Guest (own profile) | Admin | SuperAdmin

🟩 **Read Model:** Guest Data (name, email, phone, loyalty tier, preferences)

🟦 **Command:** Update Guest
> `guestId, fullName?, email?, phone?, loyaltyTier?, preferences?`

◼️ **Invariant:** Guest can only edit their own profile (except admin/superadmin)

🟧 **Event:** Guest Contact Info Updated
> `guestId`

🟧 **Event:** Guest Loyalty Tier Changed *(if tier changed)*
> `guestId, tier (BRONZE | SILVER | GOLD | PLATINUM)`

---

### Read Models — Guest

🟩 **Guest List** *(paginated, admin/superadmin only)*
> `fullName, email, phone, document, loyaltyTier`

🟩 **Guest Stats**
> count by loyalty tier

🟩 **Guest Detail**
> `fullName, email, phone, document, loyaltyTier, preferences`

---

### ⬜ Questions — Guest

- Is there a history of loyalty tier changes?
- Are preferences free-text or from a predefined catalog?
- What is the business rule for loyalty tier upgrade/downgrade?

---

## Bounded Context: Inventory (Room Management)

### Flow: Room Creation

🟨 **Actor:** Admin | SuperAdmin

🟦 **Command:** Create Room
> `number, type (SINGLE|DOUBLE|SUITE), floor, capacity, pricePerNight, amenities[]`

◼️ **Invariant:** Room number must be unique

🟧 **Event:** Room Created
> `roomId, number, type, status=AVAILABLE`

---

### Flow: Room Update

🟨 **Actor:** Admin | SuperAdmin

🟩 **Read Model:** Room Detail (number, type, floor, capacity, price, amenities, status)

🟦 **Command:** Update Room
> `roomId, pricePerNight?, amenities?`

🟧 **Event:** Room Updated
> `roomId`

---

### Flow: Room Status Change

🟨 **Actor:** Admin | SuperAdmin

🟩 **Read Model:** Room Detail (current status)

🟦 **Command:** Change Room Status
> `roomId, newStatus`

◼️ **Invariant:** Room state machine

| From | To | Condition |
|------|----|-----------|
| AVAILABLE | OCCUPIED | only via check-in |
| OCCUPIED | AVAILABLE | only via check-out/release |
| AVAILABLE / MAINTENANCE / OUT_OF_ORDER | MAINTENANCE | — |
| AVAILABLE / MAINTENANCE / OUT_OF_ORDER | OUT_OF_ORDER | — |
| MAINTENANCE / OUT_OF_ORDER | AVAILABLE | — |
| OCCUPIED | MAINTENANCE / OUT_OF_ORDER | **FORBIDDEN** |

🟧 **Event:** Room Status Changed
> `roomId, oldStatus, newStatus`

---

### Read Models — Inventory

🟩 **Room List** *(paginated, admin/superadmin only)*
> `number, type, floor, capacity, pricePerNight, status, amenities`

🟩 **Room Stats**
> count by type (SINGLE, DOUBLE, SUITE) and by status (AVAILABLE, OCCUPIED, MAINTENANCE, OUT_OF_ORDER)

🟩 **Room Availability** *(used by Reservation BC)*
> `type, period, available count, price`

---

### ⬜ Questions — Inventory

- Is there a maintenance history for rooms?
- Are amenities free-text or from a catalog?
- Does the nightly rate vary by season?

---

## Bounded Context: Reservation (Reservation Management)

### Flow: Reservation Creation

🟨 **Actor:** Guest | Admin | SuperAdmin

🟩 **Read Model:** Room Availability (type, period, availability)
🟩 **Read Model:** Guest Data (loyalty tier -> VIP status)

🟦 **Command:** Create Reservation
> `guestId, checkIn, checkOut, roomType (SINGLE|DOUBLE|SUITE)`

◼️ **Invariant:** Check-in cannot be in the past
◼️ **Invariant:** Minimum stay: 1 night
◼️ **Invariant:** Maximum stay: 365 nights
◼️ **Invariant:** Check-out must be after check-in
◼️ **Invariant:** VIP guest (PLATINUM): can book up to 90 days in advance
◼️ **Invariant:** Regular guest: can book up to 60 days in advance
◼️ **Invariant:** Rooms of the requested type must be available for the period
◼️ **Invariant:** No overlapping reservations on the same room

🟧 **Event:** Reservation Created
> `reservationId, guestId, roomType, period, status=PENDING`

---

### Flow: Reservation Confirmation

🟨 **Actor:** Admin | SuperAdmin

🟩 **Read Model:** Reservation Detail (current status, guest data)

🟦 **Command:** Confirm Reservation
> `reservationId`

◼️ **Invariant:** Reservation must be in PENDING status

🟧 **Event:** Reservation Confirmed
> `reservationId, confirmedAt`

---

### Flow: Check-In

🟨 **Actor:** Admin | SuperAdmin

🟩 **Read Model:** Reservation Detail (status, roomType)
🟩 **Read Model:** Room Availability (available rooms of type)

🟦 **Command:** Check In Guest
> `reservationId, roomNumber`

◼️ **Invariant:** Reservation must be in CONFIRMED status
◼️ **Invariant:** Room must be AVAILABLE

🟧 **Event:** Guest Checked In
> `reservationId, roomNumber, checkedInAt`

🟪 **Policy:** Whenever Guest Checked In, then Occupy Room
> `Room.status = OCCUPIED`

🟧 **Event:** Room Status Changed
> `roomId, AVAILABLE -> OCCUPIED`

---

### Flow: Check-Out

🟨 **Actor:** Admin | SuperAdmin

🟩 **Read Model:** Reservation Detail (status, assigned room)

🟦 **Command:** Check Out Guest
> `reservationId`

◼️ **Invariant:** Reservation must be in CHECKED_IN status

🟧 **Event:** Guest Checked Out
> `reservationId, checkedOutAt`

🟪 **Policy:** Whenever Guest Checked Out, then Release Room
> `Room.status = AVAILABLE`

🟧 **Event:** Room Status Changed
> `roomId, OCCUPIED -> AVAILABLE`

---

### Flow: Reservation Cancellation

🟨 **Actor:** Guest (own reservation) | Admin | SuperAdmin

🟩 **Read Model:** Reservation Detail (current status)

🟦 **Command:** Cancel Reservation
> `reservationId, reason`

◼️ **Invariant:** Reservation must be in PENDING or CONFIRMED status
◼️ **Invariant:** Cannot cancel if already CHECKED_IN, CHECKED_OUT, or CANCELLED

🟧 **Event:** Reservation Cancelled
> `reservationId, reason, cancelledAt`

---

### Flow: Special Requests

🟨 **Actor:** Guest (own reservation) | Admin | SuperAdmin

🟩 **Read Model:** Reservation Detail (status, existing special requests)

🟦 **Command:** Add Special Request
> `reservationId, requestType, description`
> requestType: `EARLY_CHECK_IN | LATE_CHECK_OUT | EXTRA_BED | DIETARY_RESTRICTION | SPECIAL_OCCASION | OTHER`

◼️ **Invariant:** Maximum of 5 special requests per reservation
◼️ **Invariant:** Cannot add if reservation is CANCELLED or CHECKED_OUT

🟧 **Event:** Special Request Added
> `reservationId, requestId, type, status=PENDING`

---

🟨 **Actor:** Admin | SuperAdmin

🟦 **Command:** Fulfill Special Request
> `reservationId, requestId`

🟧 **Event:** Special Request Fulfilled
> `reservationId, requestId, fulfilledAt`

---

### State Machine — Reservation

```mermaid
stateDiagram-v2
    [*] --> PENDING
    PENDING --> CONFIRMED
    PENDING --> CANCELLED
    CONFIRMED --> CHECKED_IN
    CHECKED_IN --> CHECKED_OUT
    CHECKED_OUT --> [*]
    CANCELLED --> [*]
```

### State Machine — Special Request

```mermaid
stateDiagram-v2
    [*] --> PENDING
    PENDING --> FULFILLED
    PENDING --> CANCELLED
    FULFILLED --> [*]
    CANCELLED --> [*]
```

---

### Read Models — Reservation

🟩 **Reservation List** *(paginated)*
> Filterable by: `status, roomType, guestId`
> Data: `id, guest, period, roomType, status, assignedRoom`

🟩 **Reservation Detail**
> `reservationId, guest (name, email, phone, isVip), period, roomType, assignedRoomNumber, status, specialRequests[], timestamps (created, confirmed, checkedIn, checkedOut, cancelled)`

🟩 **Reservation Stats**
> count by status, count by roomType, today's check-ins, today's check-outs

---

### ⬜ Questions — Reservation

- Is there billing/payment associated with reservations?
- Is there a cancellation policy (fees, deadlines)?
- Are notifications sent to guests on status changes?
- How does overbooking work? Is it allowed?

---

## Bounded Context Integration (Context Map)

```mermaid
graph LR
    IAM -- GuestGateway --> Guest
    Reservation -- GuestGateway --> Guest
    Reservation -- InventoryGateway --> Inventory
```

### Integration Patterns

| Source | Target | Gateway | Operation |
|--------|---------|---------|----------|
| IAM | Guest | GuestGateway | Create guest during actor registration |
| Reservation | Guest | GuestGateway | Fetch guest info (name, VIP status) |
| Reservation | Inventory | InventoryGateway | Check room availability |
| Reservation | Inventory | InventoryGateway | Occupy/release room (check-in/check-out) |

---

## Actors (System Roles)

🟨 **SuperAdmin**
> System administrator. No associated account. Full access to all bounded contexts.

🟨 **Admin**
> Property manager / Front desk. Associated with an Account (tenant). Can: manage rooms, confirm/check-in/check-out reservations, view guests.

🟨 **Guest**
> Hotel guest. Associated with an Account + Guest entity. Can: view/create/cancel own reservations, add special requests, edit own profile.

---

## Consolidated Timeline (Main Flow)

```mermaid
sequenceDiagram
    participant V as Visitor
    participant IAM
    participant G as Guest
    participant R as Reservation
    participant I as Inventory

    Note over V: Registration & Auth
    V->>IAM: Register Actor
    IAM->>IAM: Account Created
    IAM->>G: Actor Registered (via GuestGateway)
    G->>G: Guest Created
    V->>IAM: Login
    IAM->>V: Actor Authenticated (token)

    Note over V: Booking (as Guest)
    V->>R: Create Reservation
    R->>G: Fetch Guest Info (VIP status)
    G-->>R: Guest Info
    R->>I: Check Availability
    I-->>R: Available rooms
    R->>R: Reservation Created (PENDING)

    Note over V: Operations (as Admin)
    V->>R: Confirm Reservation
    R->>R: Reservation Confirmed

    V->>R: Check In Guest (roomNumber)
    R->>I: Occupy Room
    I->>I: Room Status Changed (OCCUPIED)
    R->>R: Guest Checked In

    V->>R: Check Out Guest
    R->>I: Release Room
    I->>I: Room Status Changed (AVAILABLE)
    R->>R: Guest Checked Out
```
