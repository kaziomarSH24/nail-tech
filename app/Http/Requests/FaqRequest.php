<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class FaqRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => 'required|string',
            'answer' => 'required|string',
            'is_active' => 'required|boolean',
        ];
    }
}
