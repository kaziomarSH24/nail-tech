<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class UpdateInventoryRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_name' => 'sometimes|string|max:255',
            'current_quantity' => 'sometimes|integer|min:0',
            'reorder_threshold' => 'sometimes|integer|min:1',
            'reminder_frequency_days' => 'nullable|integer|min:1',
        ];
    }
}

