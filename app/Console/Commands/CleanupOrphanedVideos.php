<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Lesson;
use Carbon\Carbon;

class CleanupOrphanedVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete video files that are not attached to any lesson (orphaned)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting orphaned video cleanup...');

        $disk = Storage::disk(config('filesystems.default', 'public'));
        $directory = 'lessons/videos';

        // Get all files from the directory
        $files = $disk->files($directory);
        $deletedCount = 0;

        foreach ($files as $file) {
            // Check file modification time
            $lastModified = $disk->lastModified($file);
            $fileAgeInHours = Carbon::createFromTimestamp($lastModified)->diffInHours(now());

            // Skip files newer than 24 hours (User might still be creating the lesson)
            if ($fileAgeInHours < 24) {
                continue;
            }

            // Check if file path exists in database
            // Note: Our database stores relative path like 'lessons/videos/abc.mp4'
            $existsInDb = Lesson::where('video_path', $file)->exists();

            if (!$existsInDb) {
                $disk->delete($file);
                $this->info("Deleted orphaned file: {$file}");
                $deletedCount++;
            }
        }

        $this->info("Cleanup complete. Total deleted files: {$deletedCount}");
    }
}
