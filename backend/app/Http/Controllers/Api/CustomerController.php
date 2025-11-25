<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->with(['subscriptions', 'unpaidInvoices']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->get('per_page', 15);
        $customers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'nullable|string|max:50',
            'type' => 'required|in:residential,business',
            'notes' => 'nullable|string',
        ]);

        $validated['code'] = Customer::generateCode();
        $validated['status'] = 'active';

        $customer = Customer::create($validated);

        AuditLog::log('create', Customer::class, $customer->id, null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer,
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'subscriptions.service',
            'subscriptions.router',
            'subscriptions.olt',
            'subscriptions.onu',
            'invoices' => fn($q) => $q->latest()->limit(10),
            'payments' => fn($q) => $q->latest()->limit(10),
        ]);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'nullable|string|max:50',
            'type' => 'sometimes|in:residential,business',
            'status' => 'sometimes|in:active,inactive,suspended',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $customer->only(array_keys($validated));
        $customer->update($validated);

        AuditLog::log('update', Customer::class, $customer->id, $oldValues, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer,
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        // Check if customer has active subscriptions
        if ($customer->activeSubscriptions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with active subscriptions',
            ], 422);
        }

        AuditLog::log('delete', Customer::class, $customer->id, $customer->toArray());

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }

    public function suspend(Customer $customer): JsonResponse
    {
        $customer->update(['status' => 'suspended']);

        // Suspend all active subscriptions
        foreach ($customer->activeSubscriptions as $subscription) {
            $subscription->update(['status' => 'suspended']);
            // Trigger suspend job here
        }

        AuditLog::log('suspend', Customer::class, $customer->id);

        return response()->json([
            'success' => true,
            'message' => 'Customer suspended successfully',
        ]);
    }

    public function activate(Customer $customer): JsonResponse
    {
        $customer->update(['status' => 'active']);

        AuditLog::log('activate', Customer::class, $customer->id);

        return response()->json([
            'success' => true,
            'message' => 'Customer activated successfully',
        ]);
    }
}
