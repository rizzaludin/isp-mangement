<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceRouter;
use App\Models\DeviceOlt;
use App\Models\DeviceOnu;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    // Router Methods
    public function indexRouters(Request $request): JsonResponse
    {
        $query = DeviceRouter::query()->withCount('subscriptions');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $routers = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $routers,
        ]);
    }

    public function storeRouter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip|unique:devices_routers',
            'type' => 'required|in:mikrotik,cisco,other',
            'api_port' => 'required|integer',
            'api_user' => 'required|string',
            'api_password' => 'required|string',
            'radius_secret' => 'nullable|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'active';

        $router = DeviceRouter::create($validated);

        AuditLog::log('create', DeviceRouter::class, $router->id);

        return response()->json([
            'success' => true,
            'message' => 'Router created successfully',
            'data' => $router,
        ], 201);
    }

    public function showRouter(DeviceRouter $router): JsonResponse
    {
        $router->loadCount('subscriptions');

        return response()->json([
            'success' => true,
            'data' => $router,
        ]);
    }

    public function updateRouter(Request $request, DeviceRouter $router): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ip_address' => 'sometimes|ip|unique:devices_routers,ip_address,' . $router->id,
            'type' => 'sometimes|in:mikrotik,cisco,other',
            'api_port' => 'sometimes|integer',
            'api_user' => 'sometimes|string',
            'api_password' => 'sometimes|string',
            'radius_secret' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        $router->update($validated);

        AuditLog::log('update', DeviceRouter::class, $router->id);

        return response()->json([
            'success' => true,
            'message' => 'Router updated successfully',
            'data' => $router,
        ]);
    }

    public function destroyRouter(DeviceRouter $router): JsonResponse
    {
        if ($router->subscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete router with active subscriptions',
            ], 422);
        }

        AuditLog::log('delete', DeviceRouter::class, $router->id);

        $router->delete();

        return response()->json([
            'success' => true,
            'message' => 'Router deleted successfully',
        ]);
    }

    // OLT Methods
    public function indexOlts(Request $request): JsonResponse
    {
        $query = DeviceOlt::query()->withCount(['onus', 'subscriptions']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $olts = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $olts,
        ]);
    }

    public function storeOlt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip|unique:devices_olt',
            'vendor' => 'required|in:huawei,zte,fiberhome,other',
            'mgmt_type' => 'required|in:ssh,telnet,api,snmp',
            'mgmt_port' => 'required|integer',
            'mgmt_user' => 'required|string',
            'mgmt_password' => 'required|string',
            'snmp_community' => 'nullable|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'active';

        $olt = DeviceOlt::create($validated);

        AuditLog::log('create', DeviceOlt::class, $olt->id);

        return response()->json([
            'success' => true,
            'message' => 'OLT created successfully',
            'data' => $olt,
        ], 201);
    }

    public function showOlt(DeviceOlt $olt): JsonResponse
    {
        $olt->loadCount(['onus', 'subscriptions']);
        $olt->load(['onus' => fn($q) => $q->where('status', 'online')]);

        return response()->json([
            'success' => true,
            'data' => $olt,
        ]);
    }

    public function updateOlt(Request $request, DeviceOlt $olt): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ip_address' => 'sometimes|ip|unique:devices_olt,ip_address,' . $olt->id,
            'vendor' => 'sometimes|in:huawei,zte,fiberhome,other',
            'mgmt_type' => 'sometimes|in:ssh,telnet,api,snmp',
            'mgmt_port' => 'sometimes|integer',
            'mgmt_user' => 'sometimes|string',
            'mgmt_password' => 'sometimes|string',
            'snmp_community' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        $olt->update($validated);

        AuditLog::log('update', DeviceOlt::class, $olt->id);

        return response()->json([
            'success' => true,
            'message' => 'OLT updated successfully',
            'data' => $olt,
        ]);
    }

    public function destroyOlt(DeviceOlt $olt): JsonResponse
    {
        if ($olt->subscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete OLT with active subscriptions',
            ], 422);
        }

        AuditLog::log('delete', DeviceOlt::class, $olt->id);

        $olt->delete();

        return response()->json([
            'success' => true,
            'message' => 'OLT deleted successfully',
        ]);
    }

    // ONU Methods
    public function indexOnus(Request $request): JsonResponse
    {
        $query = DeviceOnu::query()->with(['olt', 'customer', 'subscription']);

        if ($request->has('olt_id')) {
            $query->where('olt_id', $request->olt_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $onus = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $onus,
        ]);
    }

    public function showOnu(DeviceOnu $onu): JsonResponse
    {
        $onu->load(['olt', 'customer', 'subscription']);

        return response()->json([
            'success' => true,
            'data' => $onu,
        ]);
    }
}
