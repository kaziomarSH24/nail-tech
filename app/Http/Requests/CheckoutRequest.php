<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class CheckoutRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'items' => 'required|array',
            'items.*.variant_id' => 'required',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric', 
            'shipping_address' => 'required|array',
            'shipping_address.address1' => 'required',
            'shipping_address.zip' => 'required',
            'shipping_address.city' => 'required',
        ];
    }
}

