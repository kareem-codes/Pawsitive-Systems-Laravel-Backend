<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePetRequest extends FormRequest
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
        $petId = $this->route('pet');
        
        return [
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'species' => 'sometimes|string|in:dog,cat,bird,rabbit',
            'breed' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date|before_or_equal:today',
            'gender' => 'sometimes|in:male,female,unknown',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'microchip_id' => 'nullable|string|max:255|unique:pets,microchip_id,' . $petId,
            'allergies' => 'nullable|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'tags' => 'nullable|array',
        ];
    }
}
