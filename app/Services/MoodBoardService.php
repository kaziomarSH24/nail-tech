<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\MoodBoard;
use App\Models\MoodBoardImage;

class MoodBoardService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = MoodBoard::class;
    protected bool $cachePerUser = true;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }



    protected function getAllowedFilters(): array
    {
        return ['name'];
    }

    protected function getAllowedIncludes(): array
    {
        return ['images']; // Allow fetching albums with images
    }

    protected function getAllowedSorts(): array
    {
        return ['created_at'];
    }

    // ** custom logic */

    public function addImageToBoard($boardId, array $imageData)
    {
        return MoodBoardImage::create([
            'mood_board_id' => $boardId,
            'image_path' => $imageData['image_path'],
            'title' => $imageData['title'] ?? null,
            'description' => $imageData['description'] ?? null,
        ]);
    }

    public function getImagesByAlbum(MoodBoard $album)
    {
        return $album->images()->latest()->get();
    }

    public function deleteImage($imageId)
    {
        $image = MoodBoardImage::findOrFail($imageId);

        $this->deleteFile($image->image_path);
        $image->delete();

        return true;
    }
}
