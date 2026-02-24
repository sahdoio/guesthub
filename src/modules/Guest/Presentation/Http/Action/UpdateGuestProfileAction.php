<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Application\Command\UpdateGuestProfile;
use Modules\Guest\Application\Command\UpdateGuestProfileHandler;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Presentation\Http\Presenter\GuestProfilePresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateGuestProfileAction
{
    public function __construct(
        private UpdateGuestProfileHandler $handler,
        private GuestProfileRepository $repository,
        private InputValidator $validator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'loyalty_tier' => ['sometimes', 'string', 'in:bronze,silver,gold,platinum'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:255'],
        ], [
            'phone.regex' => 'Phone must be in E.164 format (e.g., +5511999999999).',
        ]);

        $this->handler->handle(new UpdateGuestProfile(
            guestProfileId: $uuid,
            fullName: $data['full_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            loyaltyTier: $data['loyalty_tier'] ?? null,
            preferences: $data['preferences'] ?? null,
        ));

        $profile = $this->repository->findByUuid(GuestProfileId::fromString($uuid));

        return JsonResponder::ok(['data' => GuestProfilePresenter::fromDomain($profile)]);
    }
}
