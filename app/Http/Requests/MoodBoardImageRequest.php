<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class MoodBoardImageRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mood_board_id' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'images' => 'required|array',
            'images.*' => 'image',
        ];
    }
}
