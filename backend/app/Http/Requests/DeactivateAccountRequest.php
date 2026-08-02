<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class DeactivateAccountRequest extends FormRequest
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
            'password' => ['required', 'string'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $user = $this->user();

            if (! $user) {
                return;
            }

            if (! Hash::check((string) $this->input('password'), $user->password)) {
                $validator->errors()->add('password', 'A senha informada está incorreta.');
            }

            $appointments = $user->isAdmin()
                ? Appointment::where('business_id', $user->business_id)
                : Appointment::where('user_id', $user->id);

            if ($appointments->active()->where('start_at', '>', now())->exists()) {
                $validator->errors()->add('appointments', 'Você tem agendamentos futuros ativos. Cancele-os antes de continuar.');
            }
        });
    }
}
