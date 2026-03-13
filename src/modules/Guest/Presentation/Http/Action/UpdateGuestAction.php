<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Application\Command\UpdateGuest;
use Modules\Guest\Application\Command\UpdateGuestHandler;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateGuestAction
{
    public function __construct(
        private UpdateGuestHandler $handler,
        private GuestRepository $repository,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $this->enforceOwnership($uuid);

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

        $this->handler->handle(new UpdateGuest(
            guestId: $uuid,
            fullName: $data['full_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            loyaltyTier: $data['loyalty_tier'] ?? null,
            preferences: $data['preferences'] ?? null,
        ));

        $guest = $this->repository->findByUuid(GuestId::fromString($uuid));

        return $this->responder->ok(['data' => GuestPresenter::fromDomain($guest)]);
    }

    private function enforceOwnership(string $uuid): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }
        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();
        if (in_array('admin', $roleNames, true) || in_array('superadmin', $roleNames, true)) {
            return;
        }
        // Guest role: verify ownership
        if ($user->subject_type === 'guest' && $user->subject_id) {
            $ownUuid = GuestModel::where('id', $user->subject_id)->value('uuid');
            if ($ownUuid !== $uuid) {
                abort(403, 'Access denied.');
            }
        }
    }
}
