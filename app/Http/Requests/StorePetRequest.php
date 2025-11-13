<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        return [
            'user_id' => $user && $user->isOwner() ? 'sometimes|exists:users,id' : 'sometimes|exists:users,id',
            'name' => 'required|string|max:255',
            'species' => 'required|string|in:dog,cat,bird,rabbit',
            'breed' => 'required|string|max:255',
            'birth_date' => 'required|date|before_or_equal:today',
            'gender' => 'required|in:male,female,unknown',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'microchip_id' => 'nullable|string|max:255|unique:pets,microchip_id',
            'allergies' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'tags' => 'nullable|array',
        ];
    }
}
