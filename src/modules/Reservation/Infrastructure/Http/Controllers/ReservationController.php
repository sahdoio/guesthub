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
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Infrastructure\Http\Requests\AddSpecialRequestRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CancelReservationRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CheckInRequest;
use Modules\Reservation\Infrastructure\Http\Requests\CreateReservationRequest;
use Modules\Reservation\Infrastructure\Http\Resources\ReservationResource;

final class ReservationController
{
    public function index(Request $request, ReservationRepository $repository): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $result = $repository->paginate($page, $perPage);

        return response()->json([
            'data' => ReservationResource::collection($result->items)->resolve(),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
        ]);
    }

    public function store(
        CreateReservationRequest $request,
        CreateReservationHandler $handler,
        ReservationRepository $repository,
    ): JsonResponse {
        $id = $handler->handle(new CreateReservation(
            guestProfileId: $request->validated('guest_profile_id'),
            checkIn: new DateTimeImmutable($request->validated('check_in')),
            checkOut: new DateTimeImmutable($request->validated('check_out')),
            roomType: $request->validated('room_type'),
        ));

        $reservation = $repository->findByUuid($id);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id, ReservationRepository $repository): ReservationResource
    {
        $reservation = $repository->findByUuid(ReservationId::fromString($id))
            ?? throw ReservationNotFoundException::withId(ReservationId::fromString($id));

        return new ReservationResource($reservation);
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
