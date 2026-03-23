<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayImageModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;
use Ramsey\Uuid\Uuid;

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
        $stayDir = 'stays/' . $stay->uuid->value;

        // Handle cover image
        if ($request->hasFile('cover')) {
            // Delete old cover
            if ($stay->coverImagePath !== null) {
                $disk->delete($stay->coverImagePath);
            }

            $path = $disk->putFile($stayDir, $request->file('cover'));
            $stay->setCoverImage($path);
            $this->stayRepository->save($stay);
        }

        // Handle secondary images
        if ($request->hasFile('images')) {
            $stayModel = StayModel::query()
                ->withoutGlobalScopes()
                ->where('uuid', $stay->uuid->value)
                ->first();

            $currentCount = StayImageModel::where('stay_id', $stayModel->id)->count();
            $maxNew = self::MAX_IMAGES - $currentCount;

            $files = array_slice($request->file('images'), 0, max(0, $maxNew));
            $position = StayImageModel::where('stay_id', $stayModel->id)->max('position') ?? 0;

            foreach ($files as $file) {
                $path = $disk->putFile($stayDir, $file);
                $position++;

                StayImageModel::create([
                    'uuid' => Uuid::uuid7()->toString(),
                    'stay_id' => $stayModel->id,
                    'path' => $path,
                    'position' => $position,
                    'created_at' => now()->toDateTimeString(),
                ]);
            }
        }

        return redirect()->back();
    }
}
