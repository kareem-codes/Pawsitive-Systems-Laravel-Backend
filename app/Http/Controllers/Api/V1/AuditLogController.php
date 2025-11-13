<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        // Filter by model type
        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        // Filter by model ID
        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->auditable_id);
        }

        // Filter by event
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($logs);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user');

        return response()->json($auditLog);
    }

    /**
     * Get audit logs for a specific model.
     */
    public function forModel(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $logs = AuditLog::where('auditable_type', $request->type)
            ->where('auditable_id', $request->id)
            ->with('user')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($logs);
    }
}
