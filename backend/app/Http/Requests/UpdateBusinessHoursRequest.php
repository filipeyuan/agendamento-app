<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6', 'distinct'],
            'hours.*.is_open' => ['required', 'boolean'],
            'hours.*.start_time' => ['required_if:hours.*.is_open,true', 'nullable', 'date_format:H:i'],
            'hours.*.end_time' => ['required_if:hours.*.is_open,true', 'nullable', 'date_format:H:i', 'after:hours.*.start_time'],
        ];
    }
}
