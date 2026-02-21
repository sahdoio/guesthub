<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CheckInGuest;
use Modules\Reservation\Application\Command\CheckOutGuest;
use Modules\Reservation\Application\Command\ConfirmReservation;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;
use Modules\Reservation\Application\Command\CancelReservationHandler;
use Modules\Reservation\Application\Command\CheckInGuestHandler;
use Modules\Reservation\Application\Command\CheckOutGuestHandler;
use Modules\Reservation\Application\Command\ConfirmReservationHandler;
use Modules\Reservation\Application\Command\CreateReservationHandler;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Reservation\Application\Query\ListReservations;
use Modules\Reservation\Application\Query\ListReservationsHandler;
use Modules\Shared\Application\Query\Pagination;
use Modules\Reservation\Infrastructure\Http\Requests\AddSpecialRequestRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CancelReservationRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CheckInRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CreateReservationRequest;
use DateMalformedStringException;

final class ReservationController
{
    public function index(Request $request, ListReservationsHandler $handler): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $result = $handler->handle(
            new ListReservations(
                status: $request->query('status'),
                roomType: $request->query('room_type'),
            ),
            new Pagination($page, $perPage),
        );

        return response()->json([
            'data' => array_map(fn($item) => $item->jsonSerialize(), $result->items),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
        ]);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function store(
        CreateReservationRequest $request,
        CreateReservationHandler $handler,
        GetReservationHandler $queryHandler,
    ): JsonResponse {
        $id = $handler->handle(new CreateReservation(
            guestProfileId: $request->validated('guest_profile_id'),
            checkIn: new DateTimeImmutable($request->validated('check_in')),
            checkOut: new DateTimeImmutable($request->validated('check_out')),
            roomType: $request->validated('room_type'),
        ));

        $readModel = $queryHandler->handle(new GetReservation((string) $id));

        return response()->json(['data' => $readModel], 201);
    }

    public function show(string $id, GetReservationHandler $handler): JsonResponse
    {
        $readModel = $handler->handle(new GetReservation($id));

        return response()->json(['data' => $readModel]);
    }

    public function confirm(string $id, ConfirmReservationHandler $handler): JsonResponse
    {
        $handler->handle(new ConfirmReservation(reservationId: $id));

        return response()->json(['message' => 'Reservation confirmed.']);
    }

    public function checkIn(
        string $id,
        CheckInRequest $request,
        CheckInGuestHandler $handler,
    ): JsonResponse {
        $handler->handle(new CheckInGuest(
            reservationId: $id,
            roomNumber: $request->validated('room_number'),
        ));

        return response()->json(['message' => 'Guest checked in.']);
    }

    public function checkOut(string $id, CheckOutGuestHandler $handler): JsonResponse
    {
        $handler->handle(new CheckOutGuest(reservationId: $id));

        return response()->json(['message' => 'Guest checked out.']);
    }

    public function cancel(
        string $id,
        CancelReservationRequest $request,
        CancelReservationHandler $handler,
    ): JsonResponse {
        $handler->handle(new CancelReservation(
            reservationId: $id,
            reason: $request->validated('reason'),
        ));

        return response()->json(['message' => 'Reservation cancelled.']);
    }

    public function addSpecialRequest(
        string $id,
        AddSpecialRequestRequest $request,
        AddSpecialRequestHandler $handler,
    ): JsonResponse {
        $requestId = $handler->handle(new AddSpecialRequest(
            reservationId: $id,
            requestType: $request->validated('type'),
            description: $request->validated('description'),
        ));

        return response()->json([
            'message' => 'Special request added.',
            'request_id' => (string) $requestId,
        ], 201);
    }
}
