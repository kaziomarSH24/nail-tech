<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;

trait ChunkUploadTrait
{
    /**
     * Handles the chunk upload process with validation.
     *
     * @param Request $request
     * @param string $inputName
     * @param string $directory
     * @param string $disk
     * @param array $allowedExtensions (Optional: ['mp4', 'mov', 'avi'])
     * @return JsonResponse|string|array
     */
    public function handleChunkUpload(Request $request, string $inputName, string $directory, string $disk = 'public', array $allowedExtensions = [])
    {
        $receiver = new FileReceiver($inputName, $request, HandlerFactory::classFromRequest($request));

        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        // Check if the upload has finished (100%)
        if ($save->isFinished()) {
            $file = $save->getFile();

            // Security: Check Extension (Backend Validation)
            if (!empty($allowedExtensions)) {
                $extension = $file->getClientOriginalExtension();
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    // Delete the temp file if invalid
                    unlink($file->getPathname());
                    return response()->json([
                        'ok' => false,
                        'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)
                    ], 422);
                }
            }

            return $this->saveFinishedFile($file, $directory, $disk);
        }

        // Return progress
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "ok" => true,
            "message" => "Uploading..."
        ]);
    }

    /**
     * Saves the completed file.
     */
    protected function saveFinishedFile($file, string $directory, string $disk)
    {
        $fileName = $this->createUniqueFilename($file);

        // Stream copy directly to final disk (Supports S3 large files)
        $path = $directory . '/' . $fileName;

        // Use stream to avoid memory issues with large files
        $stream = fopen($file->getPathname(), 'r+');
        Storage::disk($disk)->put($path, $stream, 'public'); // Visibility 'public' set kora holo
        fclose($stream);

        // Get file info BEFORE deleting the temp file
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Delete temporary chunk file
        unlink($file->getPathname());

        // Return detailed response array (Better for API)
        return response()->json([
            'path' => $path,
            'url' => Storage::disk($disk)->url($path), // Full URL for immediate preview
            'name' => $fileName,
            'mime_type' => $mimeType,
            'size' => $size,
            'done' => 100,
            'ok' => true,
            'message' => 'Upload complete'
        ]);
    }

    protected function createUniqueFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = \Illuminate\Support\Str::slug($originalName);

        return $safeName . "_" . time() . "." . $extension;
    }
}
