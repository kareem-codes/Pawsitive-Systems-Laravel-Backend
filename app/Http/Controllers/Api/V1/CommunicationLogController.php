<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CommunicationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CommunicationLogController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of communication logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CommunicationLog::with(['user', 'staff']);

        // Filter by user (owner)
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by staff member
        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by direction
        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('contacted_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('contacted_at', '<=', $request->to_date);
        }

        $logs = $query->latest('contacted_at')->paginate($request->per_page ?? 15);

        return response()->json($logs);
    }

    /**
     * Store a newly created communication log.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:call,email,whatsapp,sms,visit,other',
            'direction' => 'required|in:inbound,outbound',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'contacted_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        $validated['staff_id'] = $request->user()->id;

        $log = CommunicationLog::create($validated);
        $log->load(['user', 'staff']);

        return response()->json([
            'message' => 'Communication log created successfully',
            'log' => $log,
        ], 201);
    }

    /**
     * Display the specified communication log.
     */
    public function show(CommunicationLog $communicationLog): JsonResponse
    {
        $communicationLog->load(['user', 'staff']);

        return response()->json($communicationLog);
    }

    /**
     * Update the specified communication log.
     */
    public function update(Request $request, CommunicationLog $communicationLog): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:call,email,whatsapp,sms,visit,other',
            'direction' => 'sometimes|in:inbound,outbound',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'contacted_at' => 'sometimes|date',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        $communicationLog->update($validated);
        $communicationLog->load(['user', 'staff']);

        return response()->json([
            'message' => 'Communication log updated successfully',
            'log' => $communicationLog,
        ]);
    }

    /**
     * Remove the specified communication log.
     */
    public function destroy(CommunicationLog $communicationLog): JsonResponse
    {
        $communicationLog->delete();

        return response()->json([
            'message' => 'Communication log deleted successfully',
        ]);
    }

    /**
     * Get communication statistics for a user.
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = CommunicationLog::where('user_id', $request->user_id);

        if ($request->from_date) {
            $query->whereDate('contacted_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('contacted_at', '<=', $request->to_date);
        }

        $stats = [
            'total_communications' => $query->count(),
            'by_type' => $query->clone()->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type'),
            'by_direction' => $query->clone()->groupBy('direction')
                ->selectRaw('direction, count(*) as count')
                ->pluck('count', 'direction'),
            'total_call_duration' => $query->clone()
                ->where('type', 'call')
                ->sum('duration_minutes'),
            'last_contact' => $query->clone()->latest('contacted_at')->first()?->contacted_at,
        ];

        return response()->json($stats);
    }
}
