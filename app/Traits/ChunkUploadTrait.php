<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;

trait ChunkUploadTrait
{
    /**
     * Handles the chunk upload process.
     *
     * @param Request $request
     * @param string $inputName - The name of the file input field (e.g., 'video_file')
     * @param string $directory - The directory where the final file should be saved
     * @param string $disk - The storage disk (public, s3, etc.)
     * @return JsonResponse|string - Returns progress JSON (if loading) or final file path (string) if done.
     */
    public function handleChunkUpload(Request $request, string $inputName, string $directory, string $disk = 'public')
    {
        // 1. Initialize the receiver
        $receiver = new FileReceiver($inputName, $request, HandlerFactory::classFromRequest($request));

        // 2. Check if the file was uploaded
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        // 3. Receive the chunk
        $save = $receiver->receive();

        // 4. Check if the upload is finished (all chunks received)
        if ($save->isFinished()) {
            return $this->saveFinishedFile($save->getFile(), $directory, $disk);
        }

        // 5. If not finished, return the progress percentage to frontend
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true
        ]);
    }

    /**
     * Saves the completed file to the final destination.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @return string - Final file path
     */
    protected function saveFinishedFile($file, string $directory, string $disk): string
    {
        $fileName = $this->createUniqueFilename($file);

        // Move the file from the chunk temp directory to the final destination
        // We use putFileAs to stream the file if possible or move it
        Storage::disk($disk)->putFileAs($directory, $file, $fileName);

        // Delete the temporary file created by the package
        unlink($file->getPathname());

        return $directory . '/' . $fileName;
    }

    /**
     * Helper to create a unique filename.
     */
    protected function createUniqueFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace("." . $extension, "", $file->getClientOriginalName()); // Filename without extension
        $filename .= "_" . md5(time()) . "." . $extension;

        return $filename;
    }
}
