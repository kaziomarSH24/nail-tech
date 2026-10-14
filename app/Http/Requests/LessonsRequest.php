<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class LessonsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'section_name' => ['nullable', 'string', 'max:255'],
            'lesson_type' => ['nullable', Rule::in(['video', 'article', 'image'])],
            'description' => ['nullable', 'string'],

            // Video fields (only required when lesson_type is video or not specified)
            'video_type' => ['required_if:lesson_type,video', 'nullable', Rule::in(['upload', 'url'])],
            'video_path' => ['required_if:video_type,upload', 'nullable', 'string'],
            'video_url' => ['required_if:video_type,url', 'nullable', 'url'],

            // Image field (for article/image lessons - direct file or path)
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,gif,avif,svg', 'max:10240'],
            'image_path' => ['nullable', 'string'],

            'duration' => ['nullable', 'integer', 'min:0'],
            'is_free_preview' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'], 
        ];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => 'The image must be a valid image file (jpeg, png, jpg, webp, gif, avif, svg).',
            'image.max' => 'The image size may not be greater than 10MB.',
        ];
    }
}
