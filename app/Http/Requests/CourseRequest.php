<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class CourseRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'reward_points' => ['nullable', 'integer'],
            'duration' => ['nullable', 'string', 'max:100'],
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
