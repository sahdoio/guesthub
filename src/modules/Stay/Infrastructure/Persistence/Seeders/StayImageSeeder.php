<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayImageModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;

class StayImageSeeder extends Seeder
{
    /**
     * Number of unique images to download from picsum.
     * These are saved to disk and reused across all stays.
     */
    private const POOL_SIZE = 20;

    public function run(): void
    {
        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        // Step 1: Download a pool of images to the storage disk
        $this->command->info('Downloading image pool from picsum.photos...');
        $poolPaths = $this->buildImagePool($disk);
        $this->command->info(sprintf('Pool ready: %d images.', count($poolPaths)));

        if (count($poolPaths) === 0) {
            $this->command->warn('Could not download any images. Skipping.');
            return;
        }

        // Step 2: Assign images to all stays by copying from pool
        $stays = StayModel::withoutGlobalScopes()->get();
        $poolCount = count($poolPaths);

        foreach ($stays as $index => $stay) {
            $stayDir = "stays/{$stay->uuid}";
            $offset = ($index * 5) % $poolCount;

            // Cover image
            $coverSrc = $poolPaths[$offset % $poolCount];
            $coverPath = "{$stayDir}/cover-" . Str::random(8) . '.jpg';
            $disk->copy($coverSrc, $coverPath);
            $stay->update(['cover_image_path' => $coverPath]);

            // Gallery images (4 per stay) — skip if already exist
            if (StayImageModel::where('stay_id', $stay->id)->exists()) {
                continue;
            }

            for ($i = 0; $i < 4; $i++) {
                $gallerySrc = $poolPaths[($offset + $i + 1) % $poolCount];
                $path = "{$stayDir}/gallery-{$i}-" . Str::random(8) . '.jpg';
                $disk->copy($gallerySrc, $path);

                StayImageModel::create([
                    'uuid' => (string) Str::uuid7(),
                    'stay_id' => $stay->id,
                    'path' => $path,
                    'position' => $i + 1,
                    'created_at' => now(),
                ]);
            }

            if ($index % 50 === 0) {
                $this->command->info("  Processed {$index}/{$stays->count()} stays...");
            }
        }

        // Clean up pool files
        foreach ($poolPaths as $p) {
            $disk->delete($p);
        }

        $this->command->info("Done. Assigned images to {$stays->count()} stays.");
    }

    /**
     * Download images from picsum.photos and store them on disk as a reusable pool.
     *
     * @return array<int, string> Paths on disk (relative to storage root)
     */
    private function buildImagePool($disk): array
    {
        $faker = Faker::create();
        $faker->addProvider(new FakerPicsumImagesProvider($faker));

        $tempDir = sys_get_temp_dir() . '/stay-seeder-pool';
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $paths = [];

        for ($i = 0; $i < self::POOL_SIZE; $i++) {
            $tempFile = $faker->image($tempDir, 800, 600);

            if ($tempFile && file_exists($tempFile)) {
                $poolPath = "_pool/img-{$i}.jpg";
                $disk->put($poolPath, file_get_contents($tempFile));
                @unlink($tempFile);
                $paths[] = $poolPath;
                $this->command->info("  Downloaded image " . ($i + 1) . "/" . self::POOL_SIZE);
            }
        }

        @rmdir($tempDir);

        return $paths;
    }
}
