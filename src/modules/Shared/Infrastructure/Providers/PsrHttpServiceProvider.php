<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Infrastructure\Validation\LaravelInputValidator;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

final class PsrHttpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InputValidator::class, LaravelInputValidator::class);

        $this->app->bind(ServerRequestInterface::class, function ($app) {
            $illuminateRequest = $app->make('request');

            foreach ($illuminateRequest->route()?->parameters() ?? [] as $key => $value) {
                $illuminateRequest->attributes->set($key, $value);
            }

            if ($user = $illuminateRequest->user()) {
                $illuminateRequest->attributes->set('user', $user);
            }

            $request = (new PsrHttpFactory)->createRequest($illuminateRequest);

            if ($illuminateRequest->getContentTypeFormat() !== 'json' && $illuminateRequest->request->count() === 0) {
                return $request;
            }

            return $request->withParsedBody(
                array_merge($request->getParsedBody() ?? [], $illuminateRequest->getPayload()->all())
            );
        });
    }
}
