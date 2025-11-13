<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);
        
        $query = Appointment::with(['pet', 'owner', 'veterinarian']);

        // Filter by owner if user is an owner (customer)
        if ($request->user()->isOwner()) {
            $query->where('user_id', $request->user()->id);
        } else {
            // Staff can filter
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->has('veterinarian_id')) {
                $query->where('veterinarian_id', $request->veterinarian_id);
            }
            if ($request->has('pet_id')) {
                $query->where('pet_id', $request->pet_id);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('appointment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('appointment_date', '<=', $request->to_date);
        }

        // Get today's appointments
        if ($request->has('today') && $request->today) {
            $query->today();
        }

        // Get upcoming appointments
        if ($request->has('upcoming') && $request->upcoming) {
            $query->upcoming();
        }

        $appointments = $query->latest('appointment_date')->paginate($request->per_page ?? 15);

        return response()->json($appointments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);
        
        $appointment = Appointment::create($request->validated());
        $appointment->load(['pet', 'owner', 'veterinarian']);

        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);
        
        $appointment->load(['pet', 'owner', 'veterinarian', 'medicalRecord']);

        return response()->json(['data' => $appointment]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);
        
        $appointment->update($request->validated());
        $appointment->load(['pet', 'owner', 'veterinarian']);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);
        
        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully',
        ]);
    }

    /**
     * Confirm an appointment.
     */
    public function confirm(Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if (!in_array($appointment->status, ['pending'])) {
            return response()->json([
                'message' => 'Only pending appointments can be confirmed',
            ], 422);
        }

        $appointment->update(['status' => 'confirmed']);
        $appointment->load(['pet', 'owner', 'veterinarian']);

        return response()->json([
            'message' => 'Appointment confirmed successfully',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Complete an appointment.
     */
    public function complete(Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if (!in_array($appointment->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Only pending or confirmed appointments can be completed',
            ], 422);
        }

        $appointment->update(['status' => 'completed']);
        $appointment->load(['pet', 'owner', 'veterinarian']);

        return response()->json([
            'message' => 'Appointment completed successfully',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Completed or already cancelled appointments cannot be cancelled',
            ], 422);
        }

        $appointment->update(['status' => 'cancelled']);
        $appointment->load(['pet', 'owner', 'veterinarian']);

        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Mark appointment as no-show.
     */
    public function noShow(Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if (!in_array($appointment->status, ['confirmed'])) {
            return response()->json([
                'message' => 'Only confirmed appointments can be marked as no-show',
            ], 422);
        }

        $appointment->update(['status' => 'no_show']);
        $appointment->load(['pet', 'owner', 'veterinarian']);

        return response()->json([
            'message' => 'Appointment marked as no-show',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Get available time slots for a given date and veterinarian.
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'veterinarian_id' => 'required|exists:users,id',
            'duration_minutes' => 'integer|min:15|max:240',
        ]);

        $date = $request->date;
        $veterinarianId = $request->veterinarian_id;
        $durationMinutes = $request->duration_minutes ?? 30;

        // Get clinic hours (default 9 AM - 5 PM)
        $startHour = 9;
        $endHour = 17;

        // Get existing appointments for this vet on this date
        $existingAppointments = Appointment::where('veterinarian_id', $veterinarianId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->select('appointment_date', 'duration_minutes')
            ->get();

        // Generate all possible time slots
        $availableSlots = [];
        $currentTime = \Carbon\Carbon::parse($date)->setTime($startHour, 0, 0);
        $endTime = \Carbon\Carbon::parse($date)->setTime($endHour, 0, 0);

        while ($currentTime->lt($endTime)) {
            $slotEnd = $currentTime->copy()->addMinutes($durationMinutes);
            
            if ($slotEnd->lte($endTime)) {
                $isAvailable = true;

                // Check if this slot conflicts with existing appointments
                foreach ($existingAppointments as $appointment) {
                    $appointmentStart = \Carbon\Carbon::parse($appointment->appointment_date);
                    $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration_minutes);

                    // Check for overlap
                    if ($currentTime->lt($appointmentEnd) && $slotEnd->gt($appointmentStart)) {
                        $isAvailable = false;
                        break;
                    }
                }

                if ($isAvailable) {
                    $availableSlots[] = [
                        'time' => $currentTime->format('H:i'),
                        'datetime' => $currentTime->format('Y-m-d H:i:s'),
                    ];
                }
            }

            $currentTime->addMinutes(30); // 30-minute intervals
        }

        return response()->json([
            'date' => $date,
            'veterinarian_id' => $veterinarianId,
            'available_slots' => $availableSlots,
        ]);
    }

    /**
     * Check if a specific time slot is available.
     */
    public function checkSlotAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'appointment_date' => 'required|date',
            'veterinarian_id' => 'required|exists:users,id',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'appointment_id' => 'nullable|exists:appointments,id', // Exclude when updating
        ]);

        $appointmentDate = \Carbon\Carbon::parse($request->appointment_date);
        $veterinarianId = $request->veterinarian_id;
        $durationMinutes = $request->duration_minutes;
        $slotEnd = $appointmentDate->copy()->addMinutes($durationMinutes);

        // Query for conflicting appointments
        $query = Appointment::where('veterinarian_id', $veterinarianId)
            ->whereNotIn('status', ['cancelled', 'no_show']);

        // Exclude current appointment if updating
        if ($request->appointment_id) {
            $query->where('id', '!=', $request->appointment_id);
        }

        $conflicts = $query->get()->filter(function ($appointment) use ($appointmentDate, $slotEnd) {
            $existingStart = \Carbon\Carbon::parse($appointment->appointment_date);
            $existingEnd = $existingStart->copy()->addMinutes($appointment->duration_minutes);

            // Check for overlap
            return $appointmentDate->lt($existingEnd) && $slotEnd->gt($existingStart);
        });

        $isAvailable = $conflicts->isEmpty();

        return response()->json([
            'available' => $isAvailable,
            'conflicts' => $isAvailable ? [] : $conflicts->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'start' => $appointment->appointment_date,
                    'end' => \Carbon\Carbon::parse($appointment->appointment_date)
                        ->addMinutes($appointment->duration_minutes)
                        ->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }
}
