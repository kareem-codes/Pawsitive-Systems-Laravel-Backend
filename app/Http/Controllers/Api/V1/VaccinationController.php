<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vaccination;
use App\Http\Requests\StoreVaccinationRequest;
use App\Http\Requests\UpdateVaccinationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VaccinationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vaccination::class);
        
        $query = Vaccination::with(['pet', 'veterinarian', 'medicalRecord']);

        // Filter by pet
        if ($request->has('pet_id')) {
            $query->where('pet_id', $request->pet_id);
        }

        // Filter by veterinarian
        if ($request->has('veterinarian_id')) {
            $query->where('veterinarian_id', $request->veterinarian_id);
        }

        // Get vaccinations due soon
        if ($request->has('due_soon') && $request->due_soon) {
            $query->dueForVaccination();
        }

        // Get overdue vaccinations
        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        // Owners can only see vaccinations for their pets
        if ($request->user()->isOwner()) {
            $query->whereHas('pet', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        $vaccinations = $query->latest('administered_date')->paginate($request->per_page ?? 15);

        return response()->json($vaccinations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVaccinationRequest $request): JsonResponse
    {
        $this->authorize('create', Vaccination::class);
        
        $vaccination = Vaccination::create($request->validated());
        $vaccination->load(['pet', 'veterinarian']);

        return response()->json([
            'message' => 'Vaccination record created successfully',
            'vaccination' => $vaccination,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vaccination $vaccination): JsonResponse
    {
        $this->authorize('view', $vaccination);
        
        $vaccination->load(['pet', 'veterinarian', 'medicalRecord']);

        return response()->json($vaccination);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVaccinationRequest $request, Vaccination $vaccination): JsonResponse
    {
        $this->authorize('update', $vaccination);
        
        $vaccination->update($request->validated());
        $vaccination->load(['pet', 'veterinarian']);

        return response()->json([
            'message' => 'Vaccination record updated successfully',
            'vaccination' => $vaccination,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vaccination $vaccination): JsonResponse
    {
        $this->authorize('delete', $vaccination);
        
        $vaccination->delete();

        return response()->json([
            'message' => 'Vaccination record deleted successfully',
        ]);
    }
}
