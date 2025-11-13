<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;

class OwnerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of pet owners.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::role('owner')->with(['pets']);

        // Search by name, email, or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Filter by status (active pets)
        if ($request->has('has_active_pets') && $request->has_active_pets) {
            $query->whereHas('pets', function ($q) {
                $q->whereNull('deleted_at');
            });
        }

        $owners = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($owners);
    }

    /**
     * Store a newly created owner.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];

        $owner = User::create($validated);
        $owner->assignRole('owner');

        return response()->json([
            'message' => 'Pet owner created successfully',
            'owner' => $owner,
        ], 201);
    }

    /**
     * Display the specified owner.
     */
    public function show(User $owner): JsonResponse
    {
        $this->authorize('view', $owner);

        if (!$owner->hasRole('owner')) {
            return response()->json([
                'message' => 'User is not a pet owner',
            ], 404);
        }

        $owner->load(['pets.appointments', 'pets.medicalRecords']);

        return response()->json($owner);
    }

    /**
     * Update the specified owner.
     */
    public function update(Request $request, User $owner): JsonResponse
    {
        $this->authorize('update', $owner);

        if (!$owner->hasRole('owner')) {
            return response()->json([
                'message' => 'User is not a pet owner',
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $owner->id,
            'phone' => 'sometimes|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $owner->update($validated);

        return response()->json([
            'message' => 'Pet owner updated successfully',
            'owner' => $owner,
        ]);
    }

    /**
     * Remove the specified owner.
     */
    public function destroy(User $owner): JsonResponse
    {
        $this->authorize('delete', $owner);

        if (!$owner->hasRole('owner')) {
            return response()->json([
                'message' => 'User is not a pet owner',
            ], 404);
        }

        // Check if owner has pets
        if ($owner->pets()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete owner with existing pets. Please remove or reassign pets first.',
            ], 422);
        }

        $owner->delete();

        return response()->json([
            'message' => 'Pet owner deleted successfully',
        ]);
    }

    /**
     * Update emergency contact information.
     */
    public function updateEmergencyContact(Request $request, User $owner): JsonResponse
    {
        $this->authorize('update', $owner);

        if (!$owner->hasRole('owner')) {
            return response()->json([
                'message' => 'User is not a pet owner',
            ], 404);
        }

        $validated = $request->validate([
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
        ]);

        $owner->update($validated);

        return response()->json([
            'message' => 'Emergency contact updated successfully',
            'owner' => $owner->only(['id', 'first_name', 'last_name', 'emergency_contact_name', 'emergency_contact_phone']),
        ]);
    }

    /**
     * Get owner statistics.
     */
    public function statistics(User $owner): JsonResponse
    {
        $this->authorize('view', $owner);

        if (!$owner->hasRole('owner')) {
            return response()->json([
                'message' => 'User is not a pet owner',
            ], 404);
        }

        $stats = [
            'total_pets' => $owner->pets()->count(),
            'active_pets' => $owner->pets()->count(),
            'total_appointments' => $owner->appointments()->count(),
            'upcoming_appointments' => $owner->appointments()
                ->where('appointment_date', '>=', now())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'completed_appointments' => $owner->appointments()
                ->where('status', 'completed')
                ->count(),
            'total_invoices' => $owner->invoices()->count(),
            'pending_invoices' => $owner->invoices()
                ->where('status', 'pending')
                ->count(),
            'total_spent' => $owner->invoices()
                ->where('status', 'paid')
                ->sum('total_amount'),
        ];

        return response()->json($stats);
    }
}
