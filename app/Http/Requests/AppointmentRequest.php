<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseRequest;

class AppointmentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $rules = [
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:20'],
            'service_type' => ['required', 'string', 'max:255'],
            'appointment_at' => ['required', 'date'], // YYYY-MM-DD HH:MM:SS
            'is_reminder_on' => ['boolean'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0'],
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

