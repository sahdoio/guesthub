<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Guest\Application\Command\CreateGuestProfile;
use Modules\Guest\Application\Command\UpdateGuestProfile;
use Modules\Guest\Application\Command\CreateGuestProfileHandler;
use Modules\Guest\Application\Command\UpdateGuestProfileHandler;
use Modules\Guest\Application\Query\ListGuestProfiles;
use Modules\Guest\Application\Query\ListGuestProfilesHandler;
use Modules\Guest\Domain\Exception\GuestProfileNotFoundException;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Infrastructure\Http\Requests\CreateGuestProfileRequest;
use Modules\Guest\Infrastructure\Http\Requests\UpdateGuestProfileRequest;
use Modules\Guest\Infrastructure\Http\Resources\GuestProfileResource;

final class GuestProfileController
{
    public function index(Request $request, ListGuestProfilesHandler $handler): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $result = $handler->handle(new ListGuestProfiles($page, $perPage));

        return response()->json([
            'data' => GuestProfileResource::collection($result->items)->resolve(),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
        ]);
    }

    public function store(
        CreateGuestProfileRequest $request,
        CreateGuestProfileHandler $handler,
        GuestProfileRepository $repository,
    ): JsonResponse {
        $id = $handler->handle(new CreateGuestProfile(
            fullName: $request->validated('full_name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            document: $request->validated('document'),
            loyaltyTier: $request->validated('loyalty_tier', 'bronze'),
            preferences: $request->validated('preferences', []),
        ));

        $profile = $repository->findByUuid($id);

        return (new GuestProfileResource($profile))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $uuid, GuestProfileRepository $repository): GuestProfileResource
    {
        $profile = $repository->findByUuid(GuestProfileId::fromString($uuid))
            ?? throw GuestProfileNotFoundException::withId(GuestProfileId::fromString($uuid));

        return new GuestProfileResource($profile);
    }

    public function update(
        string $uuid,
        UpdateGuestProfileRequest $request,
        UpdateGuestProfileHandler $handler,
        GuestProfileRepository $repository,
    ): GuestProfileResource {
        $handler->handle(new UpdateGuestProfile(
            guestProfileId: $uuid,
            fullName: $request->validated('full_name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            loyaltyTier: $request->validated('loyalty_tier'),
            preferences: $request->validated('preferences'),
        ));

        $profile = $repository->findByUuid(GuestProfileId::fromString($uuid));

        return new GuestProfileResource($profile);
    }

    public function destroy(string $uuid, GuestProfileRepository $repository): JsonResponse
    {
        $profile = $repository->findByUuid(GuestProfileId::fromString($uuid))
            ?? throw GuestProfileNotFoundException::withId(GuestProfileId::fromString($uuid));

        $repository->remove($profile);

        return response()->json(null, 204);
    }
}
