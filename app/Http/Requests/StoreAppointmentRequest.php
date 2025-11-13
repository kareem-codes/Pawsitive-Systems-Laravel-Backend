<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Appointment;
use Carbon\Carbon;

class StoreAppointmentRequest extends FormRequest
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
            'pet_id' => 'required|exists:pets,id',
            'user_id' => 'required|exists:users,id',
            'veterinarian_id' => 'required|exists:users,id',
            'appointment_date' => [
                'required',
                'date',
                'after:now',
                function ($attribute, $value, $fail) {
                    $this->validateTimeSlot($value, $fail);
                },
            ],
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'type' => 'required|in:checkup,surgery,vaccination,grooming,emergency,other',
            'status' => 'nullable|in:pending,confirmed,completed,cancelled,no_show',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Validate that the time slot is available.
     */
    protected function validateTimeSlot($appointmentDate, $fail): void
    {
        if (!$this->veterinarian_id) {
            return; // Skip if veterinarian_id validation failed
        }

        $appointmentStart = Carbon::parse($appointmentDate);
        $durationMinutes = $this->duration_minutes ?? 30;
        $appointmentEnd = $appointmentStart->copy()->addMinutes($durationMinutes);

        // Check for conflicts
        $conflicts = Appointment::where('veterinarian_id', $this->veterinarian_id)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get()
            ->filter(function ($appointment) use ($appointmentStart, $appointmentEnd) {
                $existingStart = Carbon::parse($appointment->appointment_date);
                $existingEnd = $existingStart->copy()->addMinutes($appointment->duration_minutes);

                // Check for overlap
                return $appointmentStart->lt($existingEnd) && $appointmentEnd->gt($existingStart);
            });

        if ($conflicts->isNotEmpty()) {
            $fail('The selected time slot conflicts with an existing appointment.');
        }
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'appointment_date.after' => 'The appointment date must be in the future.',
            'veterinarian_id.exists' => 'The selected veterinarian does not exist.',
        ];
    }
}
