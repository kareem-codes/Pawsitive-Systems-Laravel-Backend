<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\UpdateMedicalRecordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MedicalRecordController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MedicalRecord::class);

        $query = MedicalRecord::with(['pet', 'pet.owner', 'veterinarian', 'appointment']);

        // Filter by pet
        if ($request->has('pet_id')) {
            $query->where('pet_id', $request->pet_id);
        }

        // Filter by veterinarian
        if ($request->has('veterinarian_id')) {
            $query->where('veterinarian_id', $request->veterinarian_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('visit_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('visit_date', '<=', $request->to_date);
        }

        // Owners can only see records for their pets
        if ($request->user()->isOwner()) {
            $query->whereHas('pet', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        $records = $query->latest('visit_date')->paginate($request->per_page ?? 15);

        return response()->json($records);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        $this->authorize('create', MedicalRecord::class);
        
        $record = MedicalRecord::create($request->validated());
        $record->load(['pet', 'veterinarian']);

        return response()->json([
            'message' => 'Medical record created successfully',
            'medical_record' => $record,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorize('view', $medicalRecord);
        
        $medicalRecord->load(['pet', 'veterinarian', 'appointment', 'vaccinations']);

        return response()->json($medicalRecord);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorize('update', $medicalRecord);
        
        $medicalRecord->update($request->validated());
        $medicalRecord->load(['pet', 'veterinarian']);

        return response()->json([
            'message' => 'Medical record updated successfully',
            'medical_record' => $medicalRecord,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        $this->authorize('delete', $medicalRecord);
        
        $medicalRecord->delete();

        return response()->json([
            'message' => 'Medical record deleted successfully',
        ]);
    }
}
