<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WeightRecord;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WeightRecordController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get weight history for a pet.
     */
    public function index(Pet $pet, Request $request): JsonResponse
    {
        $this->authorize('view', $pet);

        $query = $pet->weightRecords()->with('recordedBy');

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('measured_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('measured_at', '<=', $request->to_date);
        }

        $records = $query->orderBy('measured_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json($records);
    }

    /**
     * Store a new weight record.
     */
    public function store(Pet $pet, Request $request): JsonResponse
    {
        $this->authorize('update', $pet);

        $validated = $request->validate([
            'weight' => 'required|numeric|min:0.01|max:999.99',
            'unit' => 'required|in:kg,lb',
            'measured_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $validated['pet_id'] = $pet->id;
        $validated['recorded_by'] = $request->user()->id;

        $record = WeightRecord::create($validated);
        $record->load('recordedBy');

        // Update pet's current weight
        $pet->update(['weight' => $validated['weight']]);

        return response()->json([
            'message' => 'Weight record created successfully',
            'record' => $record,
        ], 201);
    }

    /**
     * Update a weight record.
     */
    public function update(Request $request, Pet $pet, WeightRecord $weightRecord): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($weightRecord->pet_id !== $pet->id) {
            return response()->json(['message' => 'Weight record does not belong to this pet'], 404);
        }

        $validated = $request->validate([
            'weight' => 'sometimes|numeric|min:0.01|max:999.99',
            'unit' => 'sometimes|in:kg,lb',
            'measured_at' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $weightRecord->update($validated);
        $weightRecord->load('recordedBy');

        return response()->json([
            'message' => 'Weight record updated successfully',
            'record' => $weightRecord,
        ]);
    }

    /**
     * Delete a weight record.
     */
    public function destroy(Pet $pet, WeightRecord $weightRecord): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($weightRecord->pet_id !== $pet->id) {
            return response()->json(['message' => 'Weight record does not belong to this pet'], 404);
        }

        $weightRecord->delete();

        return response()->json([
            'message' => 'Weight record deleted successfully',
        ]);
    }

    /**
     * Get weight analytics for a pet.
     */
    public function analytics(Pet $pet, Request $request): JsonResponse
    {
        $this->authorize('view', $pet);

        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = $pet->weightRecords();

        if ($request->from_date) {
            $query->whereDate('measured_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('measured_at', '<=', $request->to_date);
        }

        $records = $query->orderBy('measured_at', 'asc')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No weight records found',
                'analytics' => null,
            ]);
        }

        $firstRecord = $records->first();
        $lastRecord = $records->last();
        $weightChange = $lastRecord->weight - $firstRecord->weight;
        $percentageChange = $firstRecord->weight > 0 
            ? round(($weightChange / $firstRecord->weight) * 100, 2) 
            : 0;

        $analytics = [
            'total_records' => $records->count(),
            'first_weight' => $firstRecord->weight,
            'last_weight' => $lastRecord->weight,
            'weight_change' => round($weightChange, 2),
            'percentage_change' => $percentageChange,
            'unit' => $lastRecord->unit,
            'average_weight' => round($records->avg('weight'), 2),
            'min_weight' => $records->min('weight'),
            'max_weight' => $records->max('weight'),
            'trend' => $weightChange > 0 ? 'increasing' : ($weightChange < 0 ? 'decreasing' : 'stable'),
            'chart_data' => $records->map(function ($record) {
                return [
                    'date' => $record->measured_at->format('Y-m-d'),
                    'weight' => (float) $record->weight,
                    'unit' => $record->unit,
                ];
            }),
        ];

        return response()->json($analytics);
    }
}
