<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Service;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::query()->with(['customer', 'service', 'router', 'olt', 'onu']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })->orWhere('username_pppoe', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 15);
        $subscriptions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'service_id' => 'required|exists:services,id',
            'router_id' => 'nullable|exists:devices_routers,id',
            'olt_id' => 'nullable|exists:devices_olt,id',
            'onu_id' => 'nullable|exists:devices_onu,id',
            'vlan_id' => 'nullable|integer',
            'ip_static' => 'nullable|ip',
            'ip_type' => 'required|in:dhcp,static',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $service = Service::findOrFail($validated['service_id']);

        $validated['username_pppoe'] = Subscription::generateUsername($customer);
        $validated['password_pppoe'] = Subscription::generatePassword();
        $validated['status'] = 'pending';

        $subscription = DB::transaction(function () use ($validated, $service) {
            $subscription = Subscription::create($validated);

            // Create RADIUS user
            $subscription->radiusUser()->create([
                'username' => $subscription->username_pppoe,
                'password' => $subscription->password_pppoe,
                'rate_limit' => $service->rate_limit,
                'vlan_id' => $subscription->vlan_id,
                'framed_ip' => $subscription->ip_static,
                'status' => 'inactive',
            ]);

            return $subscription;
        });

        AuditLog::log('create', Subscription::class, $subscription->id, null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => $subscription->load(['customer', 'service', 'radiusUser']),
        ], 201);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load([
            'customer',
            'service',
            'router',
            'olt',
            'onu',
            'radiusUser.activeSessions',
            'invoices' => fn($q) => $q->latest()->limit(5),
        ]);

        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'sometimes|exists:services,id',
            'router_id' => 'nullable|exists:devices_routers,id',
            'olt_id' => 'nullable|exists:devices_olt,id',
            'onu_id' => 'nullable|exists:devices_onu,id',
            'vlan_id' => 'nullable|integer',
            'ip_static' => 'nullable|ip',
            'ip_type' => 'sometimes|in:dhcp,static',
            'end_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $subscription->only(array_keys($validated));
        $subscription->update($validated);

        // If service changed, update RADIUS user rate limit
        if (isset($validated['service_id']) && $subscription->radiusUser) {
            $service = Service::find($validated['service_id']);
            $subscription->radiusUser->update([
                'rate_limit' => $service->rate_limit,
            ]);
        }

        AuditLog::log('update', Subscription::class, $subscription->id, $oldValues, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully',
            'data' => $subscription,
        ]);
    }

    public function provision(Subscription $subscription): JsonResponse
    {
        if (!$subscription->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending subscriptions can be provisioned',
            ], 422);
        }

        // Queue provisioning job
        // ProvisionSubscriptionJob::dispatch($subscription);

        $subscription->update(['status' => 'active']);
        $subscription->radiusUser->update(['status' => 'active']);

        AuditLog::log('provision', Subscription::class, $subscription->id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription provisioning started',
            'data' => $subscription,
        ]);
    }

    public function suspend(Subscription $subscription): JsonResponse
    {
        if (!$subscription->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Only active subscriptions can be suspended',
            ], 422);
        }

        // Queue suspend job
        // SuspendSubscriptionJob::dispatch($subscription);

        $subscription->update(['status' => 'suspended']);
        $subscription->radiusUser->update(['status' => 'suspended']);

        AuditLog::log('suspend', Subscription::class, $subscription->id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription suspended successfully',
            'data' => $subscription,
        ]);
    }

    public function unsuspend(Subscription $subscription): JsonResponse
    {
        if (!$subscription->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'Only suspended subscriptions can be unsuspended',
            ], 422);
        }

        // Queue unsuspend job
        // UnsuspendSubscriptionJob::dispatch($subscription);

        $subscription->update(['status' => 'active']);
        $subscription->radiusUser->update(['status' => 'active']);

        AuditLog::log('unsuspend', Subscription::class, $subscription->id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription unsuspended successfully',
            'data' => $subscription,
        ]);
    }

    public function resetSession(Subscription $subscription): JsonResponse
    {
        // Queue disconnect job
        // DisconnectSessionJob::dispatch($subscription);

        AuditLog::log('reset_session', Subscription::class, $subscription->id);

        return response()->json([
            'success' => true,
            'message' => 'Session reset requested',
        ]);
    }

    public function terminate(Subscription $subscription): JsonResponse
    {
        if ($subscription->isTerminated()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is already terminated',
            ], 422);
        }

        DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => 'terminated',
                'end_date' => now(),
            ]);

            if ($subscription->radiusUser) {
                $subscription->radiusUser->update(['status' => 'inactive']);
            }
        });

        AuditLog::log('terminate', Subscription::class, $subscription->id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription terminated successfully',
        ]);
    }
}
