<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:600'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'staff_ids' => ['sometimes', 'array'],
            'staff_ids.*' => [
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('business_id', $this->user()?->business_id)->where('role', 'admin')
                ),
            ],
        ];
    }
}
