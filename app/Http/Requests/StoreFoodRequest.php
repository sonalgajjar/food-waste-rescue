<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'restaurant' && $this->user()->status === 'active';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'food_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:Veg,Non-Veg,Vegan,Dairy,Bakery,Groceries'],
            'food_type' => ['required', 'string', 'in:Cooked Meals,Packaged Goods,Raw Ingredients,Fresh Produce,Baked Goods'],
            'quantity' => ['required', 'numeric', 'min:0.1'],
            'unit' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'cooked_time' => ['required', 'date'],
            'expiry_time' => ['required', 'date', 'after:cooked_time', 'after:now'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'allergen_info' => ['nullable', 'string', 'max:500'],
            'storage_instructions' => ['nullable', 'string', 'max:255'],
            'packaging_condition' => ['nullable', 'string', 'max:255'],
            'pickup_instructions' => ['nullable', 'string', 'max:500'],
            'food_safety_declaration' => ['required', 'accepted'],
            'is_veg' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'food_safety_declaration.accepted' => 'You must explicitly certify compliance with food safety and hygiene standards.',
            'expiry_time.after' => 'The expiry date and time must be set in the future and after the preparation time.',
        ];
    }
}
