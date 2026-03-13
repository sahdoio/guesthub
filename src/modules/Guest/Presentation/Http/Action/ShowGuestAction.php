<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Domain\Exception\GuestNotFoundException;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowGuestAction
{
    public function __construct(
        private GuestRepository $repository,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $this->enforceOwnership($uuid);

        $guest = $this->repository->findByUuid(GuestId::fromString($uuid))
            ?? throw GuestNotFoundException::withId(GuestId::fromString($uuid));

        return $this->responder->ok(['data' => GuestPresenter::fromDomain($guest)]);
    }

    private function enforceOwnership(string $uuid): void
    {
        $user = auth()->user();
        if (! $user) {
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
