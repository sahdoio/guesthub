# Event Storming — GuestHub

> Mapa de cores baseado na notacao de Event Storming.
>
> Referencia: [Remote Event Storming Workshop — DDD Practitioners](https://ddd-practitioners.com/2023/03/20/remote-eventstorming-workshop/)

| Cor | Elemento | Funcao |
|-----|----------|--------|
| :orange_square: Laranja | **Event** | Algo que aconteceu no dominio (passado) |
| :blue_square: Azul | **Command** | Intencao de causar um evento |
| :yellow_square: Amarelo | **Actor** | Quem dispara o comando |
| :purple_square: Roxo | **Policy** | Regra reativa ("whenever X, then Y") |
| :green_square: Verde | **Read Model** | Projecao de dados para tomada de decisao |
| :red_square: Vermelho | **Test** | Criterios de aceitacao |
| :white_large_square: Cinza | **Question** | Duvidas ou incertezas |
| :black_large_square: Roxo escuro | **Invariant** | Regras que nunca podem ser violadas |

---

## Bounded Context: IAM (Identity & Access Management)

### Fluxo: Registro de Ator (Guest Self-Registration)

```
:yellow_square: Actor: Visitante (usuario anonimo)

:green_square: Read Model: Formulario de registro (nome, email, senha, telefone, documento)

:blue_square: Command: Register Actor
  -> accountName, name, email, password, phone, document

:black_large_square: Invariant: Email deve ser unico no sistema
:black_large_square: Invariant: Documento deve ser unico no sistema
:black_large_square: Invariant: Senha deve ter formato valido (hashed via bcrypt)

:orange_square: Event: Account Created
  -> accountId, name

:orange_square: Event: Actor Registered
  -> actorId, accountId, email, role=GUEST

:purple_square: Policy: Whenever Actor Registered (role=GUEST), then Create Guest
  -> Integracao via GuestGateway

:orange_square: Event: Guest Created
  -> guestId, email, loyaltyTier=BRONZE

:purple_square: Policy: Whenever Guest Created, then Link Actor to Guest
  -> Actor.subjectType='guest', Actor.subjectId=guestId
```

### Fluxo: Autenticacao (Login)

```
:yellow_square: Actor: Visitante (usuario anonimo)

:green_square: Read Model: Formulario de login (email, senha)

:blue_square: Command: Authenticate Actor
  -> email, password

:black_large_square: Invariant: Email deve existir no sistema
:black_large_square: Invariant: Senha deve corresponder ao hash armazenado

:orange_square: Event: Actor Authenticated
  -> actorId, token (Sanctum)

:red_square: Test: Login com credenciais validas retorna token
:red_square: Test: Login com credenciais invalidas retorna erro 401
```

### Fluxo: Logout

```
:yellow_square: Actor: Guest | Admin | SuperAdmin

:blue_square: Command: Revoke Token

:orange_square: Event: Token Revoked
  -> actorId
```

### :white_large_square: Questions — IAM

- Como funciona a recuperacao de senha?
- Existe fluxo de verificacao de email?
- Admins podem ser criados via API ou apenas via seeder?

---

## Bounded Context: Guest (Gestao de Hospedes)

### Fluxo: Criacao de Guest (via API admin)

```
:yellow_square: Actor: Admin | SuperAdmin

:green_square: Read Model: Lista de guests existentes

:blue_square: Command: Create Guest
  -> fullName, email, phone, document

:black_large_square: Invariant: Documento deve ser unico

:orange_square: Event: Guest Created
  -> guestId, email, loyaltyTier=BRONZE
```

### Fluxo: Atualizacao de Guest

```
:yellow_square: Actor: Guest (proprio perfil) | Admin | SuperAdmin

:green_square: Read Model: Guest Data (nome, email, telefone, loyalty tier, preferencias)

:blue_square: Command: Update Guest
  -> guestId, fullName?, email?, phone?, loyaltyTier?, preferences?

:black_large_square: Invariant: Guest so pode editar o proprio perfil (exceto admin/superadmin)

:orange_square: Event: Guest Contact Info Updated
  -> guestId

:orange_square: Event: Guest Loyalty Tier Changed (se tier alterado)
  -> guestId, tier (BRONZE | SILVER | GOLD | PLATINUM)
```

### Read Models — Guest

```
:green_square: Read Model: Guest List (paginado, admin/superadmin only)
  -> fullName, email, phone, document, loyaltyTier

:green_square: Read Model: Guest Stats
  -> contagem por loyalty tier

:green_square: Read Model: Guest Detail
  -> fullName, email, phone, document, loyaltyTier, preferences
```

### :white_large_square: Questions — Guest

- Existe historico de mudancas de loyalty tier?
- Preferencias sao free-text ou de um catalogo predefinido?
- Qual a regra de negocio para upgrade/downgrade de loyalty tier?

---

## Bounded Context: Inventory (Gestao de Quartos)

### Fluxo: Criacao de Quarto

```
:yellow_square: Actor: Admin | SuperAdmin

:blue_square: Command: Create Room
  -> number, type (SINGLE|DOUBLE|SUITE), floor, capacity, pricePerNight, amenities[]

:black_large_square: Invariant: Numero do quarto deve ser unico

:orange_square: Event: Room Created
  -> roomId, number, type, status=AVAILABLE
```

### Fluxo: Atualizacao de Quarto

```
:yellow_square: Actor: Admin | SuperAdmin

:green_square: Read Model: Room Detail (numero, tipo, andar, capacidade, preco, amenities, status)

:blue_square: Command: Update Room
  -> roomId, pricePerNight?, amenities?

:orange_square: Event: Room Updated
  -> roomId
```

### Fluxo: Mudanca de Status do Quarto

```
:yellow_square: Actor: Admin | SuperAdmin

:green_square: Read Model: Room Detail (status atual)

:blue_square: Command: Change Room Status
  -> roomId, newStatus

:black_large_square: Invariant: Maquina de estados do quarto
  AVAILABLE -> OCCUPIED (apenas via check-in)
  OCCUPIED -> AVAILABLE (apenas via check-out/release)
  AVAILABLE | MAINTENANCE | OUT_OF_ORDER -> MAINTENANCE
  AVAILABLE | MAINTENANCE | OUT_OF_ORDER -> OUT_OF_ORDER
  MAINTENANCE | OUT_OF_ORDER -> AVAILABLE
  OCCUPIED NAO pode ir para MAINTENANCE ou OUT_OF_ORDER

:orange_square: Event: Room Status Changed
  -> roomId, oldStatus, newStatus
```

### Read Models — Inventory

```
:green_square: Read Model: Room List (paginado, admin/superadmin only)
  -> number, type, floor, capacity, pricePerNight, status, amenities

:green_square: Read Model: Room Stats
  -> contagem por tipo (SINGLE, DOUBLE, SUITE)
  -> contagem por status (AVAILABLE, OCCUPIED, MAINTENANCE, OUT_OF_ORDER)

:green_square: Read Model: Room Availability (usado pelo Reservation BC)
  -> tipo, periodo, quantidade disponivel, preco
```

### :white_large_square: Questions — Inventory

- Existe historico de manutencao dos quartos?
- Amenities sao free-text ou de um catalogo?
- O preco por noite varia por temporada?

---

## Bounded Context: Reservation (Gestao de Reservas)

### Fluxo: Criacao de Reserva

```
:yellow_square: Actor: Guest | Admin | SuperAdmin

:green_square: Read Model: Room Availability (tipo, periodo, disponibilidade)
:green_square: Read Model: Guest Data (loyalty tier -> VIP status)

:blue_square: Command: Create Reservation
  -> guestId, checkIn, checkOut, roomType (SINGLE|DOUBLE|SUITE)

:black_large_square: Invariant: Check-in nao pode ser no passado
:black_large_square: Invariant: Estadia minima: 1 noite
:black_large_square: Invariant: Estadia maxima: 365 noites
:black_large_square: Invariant: Check-out deve ser posterior ao check-in
:black_large_square: Invariant: Guest VIP (PLATINUM): pode reservar ate 90 dias de antecedencia
:black_large_square: Invariant: Guest regular: pode reservar ate 60 dias de antecedencia
:black_large_square: Invariant: Deve haver quartos disponiveis do tipo solicitado no periodo
:black_large_square: Invariant: Nao pode haver sobreposicao de reservas no mesmo quarto

:orange_square: Event: Reservation Created
  -> reservationId, guestId, roomType, period, status=PENDING
```

### Fluxo: Confirmacao de Reserva

```
:yellow_square: Actor: Admin | SuperAdmin

:green_square: Read Model: Reservation Detail (status atual, dados do guest)

:blue_square: Command: Confirm Reservation
  -> reservationId

:black_large_square: Invariant: Reserva deve estar em status PENDING

:orange_square: Event: Reservation Confirmed
  -> reservationId, confirmedAt
```

### Fluxo: Check-In

```
:yellow_square: Actor: Admin | SuperAdmin

:green_square: Read Model: Reservation Detail (status, roomType)
:green_square: Read Model: Room Availability (quartos disponiveis do tipo)

:blue_square: Command: Check In Guest
  -> reservationId, roomNumber

:black_large_square: Invariant: Reserva deve estar em status CONFIRMED
:black_large_square: Invariant: Quarto deve estar AVAILABLE

:orange_square: Event: Guest Checked In
  -> reservationId, roomNumber, checkedInAt

:purple_square: Policy: Whenever Guest Checked In, then Occupy Room
  -> Room.status = OCCUPIED

:orange_square: Event: Room Status Changed
  -> roomId, AVAILABLE -> OCCUPIED
```

### Fluxo: Check-Out

```
:yellow_square: Actor: Admin | SuperAdmin

:green_square: Read Model: Reservation Detail (status, quarto atribuido)

:blue_square: Command: Check Out Guest
  -> reservationId

:black_large_square: Invariant: Reserva deve estar em status CHECKED_IN

:orange_square: Event: Guest Checked Out
  -> reservationId, checkedOutAt

:purple_square: Policy: Whenever Guest Checked Out, then Release Room
  -> Room.status = AVAILABLE

:orange_square: Event: Room Status Changed
  -> roomId, OCCUPIED -> AVAILABLE
```

### Fluxo: Cancelamento de Reserva

```
:yellow_square: Actor: Guest (propria reserva) | Admin | SuperAdmin

:green_square: Read Model: Reservation Detail (status atual)

:blue_square: Command: Cancel Reservation
  -> reservationId, reason

:black_large_square: Invariant: Reserva deve estar em status PENDING ou CONFIRMED
:black_large_square: Invariant: Nao pode cancelar se ja CHECKED_IN, CHECKED_OUT ou CANCELLED

:orange_square: Event: Reservation Cancelled
  -> reservationId, reason, cancelledAt
```

### Fluxo: Pedidos Especiais (Special Requests)

```
:yellow_square: Actor: Guest (propria reserva) | Admin | SuperAdmin

:green_square: Read Model: Reservation Detail (status, special requests existentes)

:blue_square: Command: Add Special Request
  -> reservationId, requestType, description
  -> requestType: EARLY_CHECK_IN | LATE_CHECK_OUT | EXTRA_BED
                  | DIETARY_RESTRICTION | SPECIAL_OCCASION | OTHER

:black_large_square: Invariant: Maximo de 5 special requests por reserva
:black_large_square: Invariant: Nao pode adicionar se reserva CANCELLED ou CHECKED_OUT

:orange_square: Event: Special Request Added
  -> reservationId, requestId, type, status=PENDING
```

```
:yellow_square: Actor: Admin | SuperAdmin

:blue_square: Command: Fulfill Special Request
  -> reservationId, requestId

:orange_square: Event: Special Request Fulfilled
  -> reservationId, requestId, fulfilledAt
```

### Maquina de Estados — Reservation

```
                    +-----------+
                    |  PENDING  |
                    +-----+-----+
                          |
                +---------+---------+
                |                   |
                v                   v
          +-----------+      +-----------+
          | CONFIRMED |      | CANCELLED |
          +-----+-----+      +-----------+
                |
                v
          +-----------+
          | CHECKED_IN|
          +-----+-----+
                |
                v
          +------------+
          | CHECKED_OUT|
          +------------+
```

### Maquina de Estados — Special Request

```
          +---------+
          | PENDING |
          +----+----+
               |
         +-----+-----+
         |           |
         v           v
   +-----------+ +-----------+
   | FULFILLED | | CANCELLED |
   +-----------+ +-----------+
```

### Read Models — Reservation

```
:green_square: Read Model: Reservation List (paginado)
  -> filtravel por: status, roomType, guestId
  -> dados: id, guest, periodo, roomType, status, assignedRoom

:green_square: Read Model: Reservation Detail
  -> reservationId, guest (nome, email, telefone, isVip)
  -> periodo, roomType, assignedRoomNumber, status
  -> specialRequests[], timestamps (created, confirmed, checkedIn, checkedOut, cancelled)

:green_square: Read Model: Reservation Stats
  -> contagem por status
  -> contagem por roomType
  -> check-ins de hoje
  -> check-outs de hoje
```

### :white_large_square: Questions — Reservation

- Existe cobranca/pagamento associado a reserva?
- Ha politica de cancelamento (multa, prazo)?
- Notificacoes sao enviadas ao guest em mudancas de status?
- Como funciona overbooking? E permitido?

---

## Integracao entre Bounded Contexts (Context Map)

```
+-------------------+          +-------------------+
|                   |  Guest   |                   |
|       IAM         |--------->|      Guest        |
|                   | Gateway  |                   |
+-------------------+          +-------------------+
                                       ^
                                       | Guest
                                       | Gateway
                                       |
+-------------------+          +-------------------+
|                   | Inventory|                   |
|    Inventory      |<---------|   Reservation     |
|                   | Gateway  |                   |
+-------------------+          +-------------------+
```

### Padroes de Integracao

| Origem | Destino | Gateway | Operacao |
|--------|---------|---------|----------|
| IAM | Guest | GuestGateway | Criar guest durante registro de ator |
| Reservation | Guest | GuestGateway | Buscar info do guest (nome, VIP status) |
| Reservation | Inventory | InventoryGateway | Verificar disponibilidade de quartos |
| Reservation | Inventory | InventoryGateway | Ocupar/liberar quarto (check-in/check-out) |

---

## Actors (Papeis do Sistema)

```
:yellow_square: SuperAdmin
  -> Administrador do sistema
  -> Sem account associado
  -> Acesso total a todos os bounded contexts

:yellow_square: Admin
  -> Gerente de propriedade / Front desk
  -> Associado a um Account (tenant)
  -> Pode: gerenciar quartos, confirmar/check-in/check-out reservas, ver guests

:yellow_square: Guest
  -> Hospede
  -> Associado a um Account + entidade Guest
  -> Pode: ver/criar/cancelar proprias reservas, adicionar special requests, editar proprio perfil
```

---

## Timeline Consolidada (Fluxo Principal)

```
Visitante           IAM                    Guest              Reservation          Inventory
    |                 |                      |                     |                    |
    |--- Register --->|                      |                     |                    |
    |                 |-- Account Created -->|                     |                    |
    |                 |-- Actor Registered ->|                     |                    |
    |                 |                      |-- Guest Created     |                    |
    |                 |                      |                     |                    |
    |--- Login ------>|                      |                     |                    |
    |                 |-- Authenticated      |                     |                    |
    |                 |                      |                     |                    |
    |                 |                      |                     |                    |
  Guest               |                      |                     |                    |
    |                 |                      |                     |                    |
    |----------------------------------------+-- Create Reserv -->|                    |
    |                 |                      |<-- Guest Info ------|                    |
    |                 |                      |                     |--- Check Avail --->|
    |                 |                      |                     |<-- Available ------|
    |                 |                      |                     |                    |
    |                 |                      |  Reservation Created|                    |
    |                 |                      |                     |                    |
  Admin               |                      |                     |                    |
    |                 |                      |                     |                    |
    |---------------------------------------------Confirm ------->|                    |
    |                 |                      |  Reservation Confirmed                   |
    |                 |                      |                     |                    |
    |---------------------------------------------Check In ------>|                    |
    |                 |                      |                     |--- Occupy Room --->|
    |                 |                      |  Guest Checked In   | Room Occupied      |
    |                 |                      |                     |                    |
    |---------------------------------------------Check Out ----->|                    |
    |                 |                      |                     |--- Release Room -->|
    |                 |                      |  Guest Checked Out  | Room Available     |
    |                 |                      |                     |                    |
```