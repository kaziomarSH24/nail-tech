<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class Financerequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'string'],
            'record_date' => ['required', 'date'],
            'client_or_vendor_name' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
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
