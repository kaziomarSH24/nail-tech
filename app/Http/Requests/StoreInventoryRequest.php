<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_name' => 'required|string|max:255',
            'price'=> 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', //5mb
            'source' => ['required', Rule::in(['shop', 'external'])],
            'linked_product_id' => 'nullable|exists:products,id|required_if:source,shop',
            'current_quantity' => 'required|integer|min:0',
            'reorder_threshold' => 'required|integer|min:1',
            'reminder_frequency_days' => 'nullable|integer|min:1',
        ];
    }
}
