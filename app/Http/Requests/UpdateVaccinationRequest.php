<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVaccinationRequest extends FormRequest
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
        return [
            'pet_id' => 'sometimes|exists:pets,id',
            'veterinarian_id' => 'sometimes|exists:users,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'vaccine_name' => 'sometimes|string|max:255',
            'administered_date' => 'sometimes|date',
            'next_due_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
