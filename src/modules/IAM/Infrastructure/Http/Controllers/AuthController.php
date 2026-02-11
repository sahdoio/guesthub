<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Application\Command\AuthenticateActor;
use Modules\IAM\Application\Command\AuthenticateActorHandler;
use Modules\IAM\Application\Command\RegisterActor;
use Modules\IAM\Application\Command\RegisterActorHandler;
use Modules\IAM\Application\Command\RevokeToken;
use Modules\IAM\Application\Command\RevokeTokenHandler;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Infrastructure\Http\Requests\LoginRequest;
use Modules\IAM\Infrastructure\Http\Requests\RegisterRequest;
use Modules\IAM\Infrastructure\Http\Resources\ActorResource;

final class AuthController
{
    public function register(
        RegisterRequest $request,
        RegisterActorHandler $handler,
        ActorRepository $repository,
    ): JsonResponse {
        $id = $handler->handle(new RegisterActor(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            phone: $request->validated('phone'),
            document: $request->validated('document'),
        ));

        $actor = $repository->findByUuid($id);

        return (new ActorResource($actor))
            ->response()
            ->setStatusCode(201);
    }

    public function login(
        LoginRequest $request,
        AuthenticateActorHandler $handler,
    ): JsonResponse {
        $result = $handler->handle(new AuthenticateActor(
            email: $request->validated('email'),
            password: $request->validated('password'),
        ));

        return response()->json([
            'token' => $result['token'],
            'actor_id' => $result['actor_id'],
        ]);
    }

    public function logout(
        Request $request,
        RevokeTokenHandler $handler,
    ): JsonResponse {
        $handler->handle(new RevokeToken(
            actorEmail: $request->user()->email,
        ));

        return response()->json(['message' => 'Logged out.']);
    }
}
