<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordRequest extends FormRequest
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
            'appointment_id' => 'nullable|exists:appointments,id',
            'visit_date' => 'sometimes|date',
            'weight' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|min:0|max:50',
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'prescriptions' => 'nullable|string',
            'procedures' => 'nullable|string',
            'notes' => 'nullable|string',
            'next_visit_date' => 'nullable|date',
        ];
    }
}
