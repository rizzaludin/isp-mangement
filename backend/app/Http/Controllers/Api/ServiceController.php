<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->withCount('subscriptions');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        $services = $query->orderBy('price')->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:services',
            'speed_up' => 'required|integer|min:1',
            'speed_down' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:daily,weekly,monthly,yearly',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = 'active';

        $service = Service::create($validated);

        AuditLog::log('create', Service::class, $service->id, null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => $service,
        ], 201);
    }

    public function show(Service $service): JsonResponse
    {
        $service->loadCount('subscriptions');

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:services,code,' . $service->id,
            'speed_up' => 'sometimes|integer|min:1',
            'speed_down' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'billing_cycle' => 'sometimes|in:daily,weekly,monthly,yearly',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $oldValues = $service->only(array_keys($validated));
        $service->update($validated);

        AuditLog::log('update', Service::class, $service->id, $oldValues, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully',
            'data' => $service,
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        if ($service->subscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete service with existing subscriptions',
            ], 422);
        }

        AuditLog::log('delete', Service::class, $service->id, $service->toArray());

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully',
        ]);
    }
}
