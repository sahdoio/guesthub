<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CheckInGuest;
use Modules\Reservation\Application\Command\CheckOutGuest;
use Modules\Reservation\Application\Command\ConfirmReservation;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Handler\AddSpecialRequestHandler;
use Modules\Reservation\Application\Handler\CancelReservationHandler;
use Modules\Reservation\Application\Handler\CheckInGuestHandler;
use Modules\Reservation\Application\Handler\CheckOutGuestHandler;
use Modules\Reservation\Application\Handler\ConfirmReservationHandler;
use Modules\Reservation\Application\Handler\CreateReservationHandler;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Presentation\Http\Requests\AddSpecialRequestRequest;
use Modules\Reservation\Presentation\Http\Requests\CancelReservationRequest;
use Modules\Reservation\Presentation\Http\Requests\CheckInRequest;
use Modules\Reservation\Presentation\Http\Requests\CreateReservationRequest;
use Modules\Reservation\Presentation\Http\Resources\ReservationResource;

final class ReservationController
{
    public function store(
        CreateReservationRequest $request,
        CreateReservationHandler $handler,
        ReservationRepository $repository,
    ): JsonResponse {
        $id = $handler->handle(new CreateReservation(
            guestFullName: $request->validated('guest_full_name'),
            guestEmail: $request->validated('guest_email'),
            guestPhone: $request->validated('guest_phone'),
            guestDocument: $request->validated('guest_document'),
            isVip: $request->boolean('is_vip'),
            checkIn: new DateTimeImmutable($request->validated('check_in')),
            checkOut: new DateTimeImmutable($request->validated('check_out')),
            roomType: $request->validated('room_type'),
        ));

        $reservation = $repository->findById($id);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id, ReservationRepository $repository): ReservationResource
    {
        $reservation = $repository->findById(ReservationId::fromString($id))
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
