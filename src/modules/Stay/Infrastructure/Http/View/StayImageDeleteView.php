<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Stay\Domain\Repository\StayRepository;

final class StayImageDeleteView
{
    public function __construct(
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request, string $slug, string $imageUuid): RedirectResponse
    {
        $stay = $this->stayRepository->findBySlug($slug);
        abort_if($stay === null, 404);

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        // Check if deleting cover image
        if ($imageUuid === 'cover') {
            if ($stay->coverImagePath !== null) {
                $disk->delete($stay->coverImagePath);
                $stay->setCoverImage(null);
                $this->stayRepository->save($stay);
            }

            return redirect()->back();
        }

        // Delete secondary image
        $path = $this->stayRepository->deleteImageByUuid($imageUuid);
        abort_if($path === null, 404);

        $disk->delete($path);

        return redirect()->back();
    }
}
