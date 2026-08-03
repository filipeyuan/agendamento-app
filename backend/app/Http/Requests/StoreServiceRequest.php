<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    private const FREE_PLAN_SERVICE_LIMIT = 1;

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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $business = $this->user()?->business;

            if (! $business || $business->isPro()) {
                return;
            }

            $count = Service::where('business_id', $business->id)->count();

            if ($count >= self::FREE_PLAN_SERVICE_LIMIT) {
                $validator->errors()->add(
                    'plan',
                    'O plano Free permite cadastrar até '.self::FREE_PLAN_SERVICE_LIMIT.' serviço. Assine o Pro pra cadastrar mais.'
                );
            }
        });
    }
}
