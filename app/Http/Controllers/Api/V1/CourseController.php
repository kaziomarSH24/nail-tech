<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Requests\LessonsRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Traits\ChunkUploadTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use FileUploadTrait, ChunkUploadTrait;
    protected CourseService $courseService;
    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
        $this->authorizeResource(Course::class, 'course');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = $this->courseService->getAll();
        if ($courses->isEmpty()) {
            return response_error('No courses found.', [], 404);
        }
        return CourseResource::collection($courses)->additional(
            [
                'ok' => true,
                'message' => 'Courses retrieved successfully.'
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CourseRequest $request)
    {
        $data = $request->validated();
        //handele file upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->handleFileUpload($request, 'thumbnail', 'courses/thumbnails', null, null, 85, true);
        }
        $courses = $this->courseService->create($data);

        return new CourseResource($courses)->additional(
            [
                'ok' => true,
                'message' => 'Course created successfully.'
            ]
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $course = $this->courseService->getById($course->id, ['lessons']);
        return new CourseResource($course)->additional(
            [
                'ok' => true,
                'message' => 'Course retrieved successfully.'
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CourseRequest $request, Course $course)
    {
        $data = $request->validated();
        //handele file upload
        if ($request->hasFile('thumbnail')) {
            //remove old file and upload new file
            if ($course->thumbnail) {
                $this->deleteFile($course->thumbnail);
            }
            $data['thumbnail'] = $this->handleFileUpload($request, 'thumbnail', 'courses/thumbnails', null, null, 85, true);
        }
        $course = $this->courseService->update($course->id, $data);

        return new CourseResource($course)->additional(
            [
                'ok' => true,
                'message' => 'Course updated successfully.'
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        // Delete associated thumbnail
        if ($course->thumbnail) {
            $this->deleteFile($course->thumbnail);
        }
        $this->courseService->delete($course->id);

        return response_success('Course deleted successfully.');
    }

    //update published status
    public function updatePublishedStatus(Request $request, Course $course)
    {
        $request->validate([
            'is_published' => 'required|boolean',
        ]);
        $course = $this->courseService->update($course->id, ['is_published' => $request->is_published]);
        return new CourseResource($course)->additional(
            [
                'ok' => true,
                'message' => 'Course published status updated successfully.'
            ]
        );
    }

    /**
     * Handle chunk upload for course videos.
     */
    public function uploadCourseVideo(Request $request)
    {
        $allowedExtensions = ['mp4', 'mov', 'avi', 'mkv'];
        return $this->handleChunkUpload($request, 'video_file', 'courses/videos', 'public', $allowedExtensions);
    }

    /**
     * Add Lesson to Course - Step 2
     */
    public function addLesson(LessonsRequest $request, $id)
    {
        $lesson = $this->courseService->addLesson($id, $request->validated());

        return response_success('Lesson added successfully.', $lesson, 201);
    }

    /**
     * Update an existing Lesson
     */
    public function updateLesson(LessonsRequest $request, $courseId, $lessonId)
    {
        $course = $this->courseService->getById($courseId);
        $lesson = $course->lessons()->findOrFail($lessonId);

        $data = $request->validated();

        // Path logic resolve for update
        if ($data['video_type'] === 'upload' && isset($data['video_path'])) {
            $finalPath = $data['video_path'];

            // Optional: Delete old video if new one is uploaded
            if ($lesson->video_type === 'upload' && $lesson->video_path) {
                $this->deleteFile($lesson->video_path);
            }
        } elseif ($data['video_type'] === 'url') {
            $finalPath = $data['video_url'];
        } else {
            // If video type/path not changed, keep old path
            $finalPath = $lesson->video_path;
        }

        $lesson->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? $lesson->description,
            'video_type' => $data['video_type'],
            'video_path' => $finalPath,
            'duration' => $data['duration'] ?? $lesson->duration,
            'sort_order' => $data['sort_order'] ?? $lesson->sort_order,
            'is_free_preview' => $data['is_free_preview'] ?? $lesson->is_free_preview,
        ]);

        return response_success('Lesson updated successfully.', $lesson);
    }

    /**
     * Delete a Lesson
     */
    public function deleteLesson($courseId, $lessonId)
    {
        $course = $this->courseService->getById($courseId);
        $lesson = $course->lessons()->findOrFail($lessonId);

        // Delete associated video file if it's an uploaded video
        if ($lesson->video_type === 'upload' && $lesson->video_path) {
            // Trait method to delete file
            $this->deleteFile($lesson->video_path);
        }

        $lesson->delete();

        return response_success('Lesson deleted successfully.');
    }

    //get single lesson details
    public function getLesson($courseId, $lessonId)
    {
        $course = $this->courseService->getById($courseId);
        $lesson = $course->lessons()->findOrFail($lessonId);
        return response_success('Lesson retrieved successfully.', $lesson);
    }
}
