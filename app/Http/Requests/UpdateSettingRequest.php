<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class UpdateSettingRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_reminder' => 'boolean',
            // 'product_restock_update' => 'boolean',
            'new_courses' => 'boolean',
        ];
    }
}

