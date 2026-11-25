<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || $user->role !== 'ngo' || $user->status !== 'active') {
            return false;
        }

        $ngo = $user->ngo;
        if (!$ngo || $ngo->status !== 'active' || $ngo->isRegistrationExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pickup_time' => ['required', 'date', 'after_or_equal:now'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
