<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Stay\Domain\Repository\StayRepository;

final class StayImageUploadView
{
    private const MAX_IMAGES = 10;

    public function __construct(
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $stay = $this->stayRepository->findBySlug($slug);
        abort_if($stay === null, 404);

        $request->validate([
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));
        $stayDir = 'stays/'.$stay->uuid->value;

        // Handle cover image
        if ($request->hasFile('cover')) {
            if ($stay->coverImagePath !== null) {
                $disk->delete($stay->coverImagePath);
            }

            $path = $disk->putFile($stayDir, $request->file('cover'));
            $stay->setCoverImage($path);
            $this->stayRepository->save($stay);
        }

        // Handle secondary images
        if ($request->hasFile('images')) {
            $currentCount = $this->stayRepository->countImages($stay->uuid);
            $maxNew = self::MAX_IMAGES - $currentCount;

            $files = array_slice($request->file('images'), 0, max(0, $maxNew));
            $position = $this->stayRepository->maxImagePosition($stay->uuid);

            foreach ($files as $file) {
                $path = $disk->putFile($stayDir, $file);
                $position++;

                $this->stayRepository->addImage($stay->uuid, $path, $position);
            }
        }

        return redirect()->back();
    }
}
