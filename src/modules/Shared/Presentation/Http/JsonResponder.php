<?php

declare(strict_types=1);

namespace Modules\Shared\Presentation\Http;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final readonly class JsonResponder
{
    public function ok(mixed $data): ResponseInterface
    {
        return $this->respond($data, 200);
    }

    public function created(mixed $data): ResponseInterface
    {
        return $this->respond($data, 201);
    }

    public function noContent(): ResponseInterface
    {
        return new Response(204);
    }

    public function error(array $errors, int $status): ResponseInterface
    {
        return $this->respond(['errors' => $errors], $status);
    }

    private function respond(mixed $data, int $status): ResponseInterface
    {
        return new Response(
            status: $status,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($data, JSON_THROW_ON_ERROR),
        );
    }
}
