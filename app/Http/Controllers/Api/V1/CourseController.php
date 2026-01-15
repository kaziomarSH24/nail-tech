<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use FileUploadTrait;
    protected CourseService $courseService;
    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = $this->courseService->getAll();
        if ($courses->isEmpty()) {
            return response_error('No courses found.',[], 404);
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
        return new CourseResource($course->loadMissing('lessons'))->additional(
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
}
