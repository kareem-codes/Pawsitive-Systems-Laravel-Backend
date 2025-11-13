<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get all settings or by group.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ClinicSetting::query();

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        $settings = $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->casted_value];
        });

        return response()->json($settings);
    }

    /**
     * Get a specific setting.
     */
    public function show(string $key): JsonResponse
    {
        $setting = ClinicSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        return response()->json([
            'key' => $setting->key,
            'value' => $setting->casted_value,
            'type' => $setting->type,
            'group' => $setting->group,
            'description' => $setting->description,
        ]);
    }

    /**
     * Update or create a setting.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'type' => 'required|in:string,integer,float,boolean,json,array',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $setting = ClinicSetting::set(
            $validated['key'],
            $validated['value'],
            $validated['type'],
            $validated['group'] ?? 'general'
        );

        if ($request->has('description')) {
            $setting->update(['description' => $validated['description']]);
        }

        return response()->json([
            'message' => 'Setting saved successfully',
            'setting' => [
                'key' => $setting->key,
                'value' => $setting->casted_value,
            ],
        ]);
    }

    /**
     * Update multiple settings at once.
     */
    public function updateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'required|in:string,integer,float,boolean,json,array',
        ]);

        foreach ($validated['settings'] as $settingData) {
            ClinicSetting::set(
                $settingData['key'],
                $settingData['value'],
                $settingData['type']
            );
        }

        return response()->json([
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Delete a setting.
     */
    public function destroy(string $key): JsonResponse
    {
        $setting = ClinicSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        $setting->delete();

        return response()->json([
            'message' => 'Setting deleted successfully',
        ]);
    }

    /**
     * Get all setting groups.
     */
    public function groups(): JsonResponse
    {
        $groups = ClinicSetting::distinct()->pluck('group');

        return response()->json($groups);
    }
}
