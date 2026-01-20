<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoodBoardImageRequest;
use App\Http\Requests\MoodBoardRequest;
use App\Http\Resources\MoodBoardResource;
use App\Models\MoodBoard;
use App\Models\MoodBoardImage;
use App\Services\MoodBoardService;
use App\Traits\FileUploadTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MoodBoardController extends Controller
{
    use AuthorizesRequests, FileUploadTrait;

    protected MoodBoardService $moodBoardService;

    public function __construct(MoodBoardService $moodBoardService)
    {
        $this->moodBoardService = $moodBoardService;
        $this->authorizeResource(MoodBoard::class, 'mood_board');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $qc = function ($query) {
            $query->where('user_id', auth()->id());
        };
        $moodBoards = $this->moodBoardService->getAll($qc);
        if ($moodBoards->isEmpty()) {
            return response_error('No mood boards found.', [], 404);
        }
        return MoodBoardResource::collection($moodBoards)->additional([
            'ok' => true,
            'message' => 'Mood boards retrieved successfully.',
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(MoodBoardRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->handleFileUpload($request, 'cover_image', 'mood_boards/cover_images', null, null, 85, true);
        }
        $moodBoard = $this->moodBoardService->create($data);
        return new MoodBoardResource($moodBoard)->additional([
            'ok' => true,
            'message' => 'Mood board created successfully.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(MoodBoard $mood_board)
    {
        $data = $this->moodBoardService->getById($mood_board->id);
        return new MoodBoardResource($data)->additional([
            'ok' => true,
            'message' => 'Mood board retrieved successfully.',
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(MoodBoardRequest $request, MoodBoard $mood_board)
    {
        $data = $request->validated();
        if ($request->hasFile('cover_image')) {
            //remove old image
            if ($mood_board->cover_image) {
                $this->deleteFile($mood_board->cover_image);
            }
            $data['cover_image'] = $this->handleFileUpload($request, 'cover_image', 'mood_boards/cover_images', null, null, 85, true);
        }

        $updatedMoodBoard = $this->moodBoardService->update($mood_board->id, $data);
        return new MoodBoardResource($updatedMoodBoard)->additional([
            'ok' => true,
            'message' => 'Mood board updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MoodBoard $mood_board)
    {
        // Delete associated cover image
        if ($mood_board->cover_image) {
            $this->deleteFile($mood_board->cover_image);
        }
        $this->moodBoardService->delete($mood_board->id);
        return response_success('Mood board deleted successfully.');
    }

    //get image from album
    public function getAlbumImages(MoodBoard $moodBoard)
    {
        $this->authorize('view', $moodBoard);
        $images = $this->moodBoardService->getImagesByAlbum($moodBoard);

        if ($images->isEmpty()) {
            return response_error('No images found in this mood board.', [], 404);
        }
        return response_success('Images fetched.', $images);
    }

    // upload image to album
    public function uploadImage(MoodBoardImageRequest $request)
    {
        $this->authorize('update', MoodBoard::find($request->mood_board_id));
        $data = $request->validated();

        $uploadedFiles = [];

        foreach ($request->file('images') as $index => $file) {
            $path = $this->handleFileUpload(
                $request,
                "images.{$index}",
                'mood_boards/mood_images',
                800,
                null,
                85,
                true
            );

            if ($path) {
                $this->moodBoardService->addImageToBoard($data['mood_board_id'], [
                    'image_path' => $path,
                    'title' => $request->input("titles.{$index}"),
                    'description' => $request->input("descriptions.{$index}"),
                ]);
                $uploadedFiles[] = $path;
            }
        }

        return response_success('Images uploaded.', $uploadedFiles);
    }

    // delete image from album
    public function deleteImage($imageId)
    {
        $image = MoodBoardImage::findOrFail($imageId);
        $this->authorize('update', MoodBoard::find($image->mood_board_id));
        $this->deleteFile($image->image_path);
        $image->delete();

        return response_success('Image deleted successfully.');
    }
}
