<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Presentation\Http;

use Modules\Shared\Presentation\Http\JsonResponder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonResponder::class)]
final class JsonResponderTest extends TestCase
{
    private JsonResponder $responder;

    protected function setUp(): void
    {
        $this->responder = new JsonResponder;
    }

    #[Test]
    public function ok_returns200_with_json_body(): void
    {
        $response = $this->responder->ok(['key' => 'value']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"key":"value"}', (string) $response->getBody());
    }

    #[Test]
    public function created_returns201_with_json_body(): void
    {
        $response = $this->responder->created(['id' => 1]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"id":1}', (string) $response->getBody());
    }

    #[Test]
    public function no_content_returns204_with_empty_body(): void
    {
        $response = $this->responder->noContent();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }

    #[Test]
    public function error_returns_given_status_with_errors_payload(): void
    {
        $response = $this->responder->error(['field' => 'required'], 422);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('{"errors":{"field":"required"}}', (string) $response->getBody());
    }
}
