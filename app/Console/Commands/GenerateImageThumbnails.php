<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductMedia;
use App\Services\Catalog\ImageThumbnailService;

class GenerateImageThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:generate-thumbnails {--force : Force regeneration of existing thumbnails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate small thumbnail versions for all uploaded product images in database and storage';

    /**
     * Execute the console command.
     */
    public function handle(ImageThumbnailService $thumbnailService): int
    {
        $force = $this->option('force');

        $query = ProductMedia::where(function ($q) {
            $q->whereNull('file_type')
              ->orWhere('file_type', 'image');
        });

        if (!$force) {
            $query->whereNull('thumbnail_path');
        }

        $mediaItems = $query->get();
        $total = $mediaItems->count();

        if ($total === 0) {
            $this->info("No product images found requiring thumbnail generation.");
            return Command::SUCCESS;
        }

        $this->info("Starting thumbnail generation for {$total} product media item(s)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $failed = 0;

        foreach ($mediaItems as $media) {
            if (empty($media->file_path)) {
                $bar->advance();
                continue;
            }

            // Skip external URLs if any
            if (str_starts_with($media->file_path, 'http://') || str_starts_with($media->file_path, 'https://')) {
                $bar->advance();
                continue;
            }

            $thumbPath = $thumbnailService->generateThumbnail($media->file_path);

            if ($thumbPath) {
                $media->update(['thumbnail_path' => $thumbPath]);
                $processed++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Thumbnail generation complete! Successfully generated/updated: {$processed}, Failed/Skipped: {$failed}.");

        return Command::SUCCESS;
    }
}
