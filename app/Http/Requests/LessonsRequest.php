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
            'description' => ['nullable', 'string'],
            'video_type' => ['required', Rule::in(['upload', 'url'])],
            'video_path' => ['required_if:video_type,upload', 'string'],
            'video_url' => ['required_if:video_type,url', 'url'],

            'duration' => ['nullable', 'integer', 'min:0'],
            'is_free_preview' => ['boolean'],
            'sort_order' => ['nullable', 'integer'], 
        ];
    }
}
