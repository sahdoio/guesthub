<?php

declare(strict_types=1);

namespace Modules\Shared\Presentation\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class JsonResponder
{
    public static function ok(mixed $data): ResponseInterface
    {
        return self::respond($data, 200);
    }

    public static function created(mixed $data): ResponseInterface
    {
        return self::respond($data, 201);
    }

    public static function noContent(): ResponseInterface
    {
        return new Response(204);
    }

    public static function error(array $errors, int $status): ResponseInterface
    {
        return self::respond(['errors' => $errors], $status);
    }

    private static function respond(mixed $data, int $status): ResponseInterface
    {
        return new Response(
            status: $status,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($data, JSON_THROW_ON_ERROR),
        );
    }
}
