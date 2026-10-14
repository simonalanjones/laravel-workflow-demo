<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'requires_signoff' => 'sometimes|boolean',
            'simulate_network_delay' => 'sometimes|boolean',
            'simulate_lost_response' => 'sometimes|boolean',
            'item_types' => 'required|array|min:1',
            'item_types.*' => 'required|string|distinct|in:accommodation,flight,car_hire',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'item_types.required' => 'Choose at least one booking item.',
            'item_types.*.in' => 'Choose accommodation, flights, or car hire.',
        ];
    }
}
