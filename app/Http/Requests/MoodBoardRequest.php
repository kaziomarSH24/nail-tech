<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class MoodBoardRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:10240'], // max 10MB
        ];

        if (request()->isMethod('PUT') || request()->isMethod('PATCH')) {
            return array_map(function ($rule) {

                if (($key = array_search('required', $rule)) !== false) {
                    $rule[$key] = 'sometimes';
                }
                return $rule;
            }, $rules);
        }

        return $rules;
    }
}
