<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Models\RadiusUser;
use App\Jobs\ProvisionSubscriptionJob;
use App\Jobs\SuspendSubscriptionJob;
use App\Jobs\UnsuspendSubscriptionJob;

class SubscriptionObserver
{
    public function created(Subscription $subscription): void
    {
        // Create RADIUS user automatically
        if (!$subscription->radiusUser) {
            RadiusUser::create([
                'subscription_id' => $subscription->id,
                'username' => $subscription->username_pppoe,
                'password' => $subscription->password_pppoe,
                'rate_limit' => $subscription->service->rate_limit,
                'vlan_id' => $subscription->vlan_id,
                'framed_ip' => $subscription->ip_static,
                'status' => 'inactive',
            ]);
        }
    }

    public function updated(Subscription $subscription): void
    {
        // Auto-queue provision when status changes to active
        if ($subscription->isDirty('status')) {
            $oldStatus = $subscription->getOriginal('status');
            $newStatus = $subscription->status;

            if ($oldStatus === 'pending' && $newStatus === 'active') {
                ProvisionSubscriptionJob::dispatch($subscription);
            }

            if ($oldStatus === 'active' && $newStatus === 'suspended') {
                SuspendSubscriptionJob::dispatch($subscription);
            }

            if ($oldStatus === 'suspended' && $newStatus === 'active') {
                UnsuspendSubscriptionJob::dispatch($subscription);
            }
        }

        // Update RADIUS user when service changes
        if ($subscription->isDirty('service_id') && $subscription->radiusUser) {
            $subscription->radiusUser->update([
                'rate_limit' => $subscription->service->rate_limit,
            ]);
        }
    }

    public function deleting(Subscription $subscription): void
    {
        // Clean up RADIUS user
        if ($subscription->radiusUser) {
            $subscription->radiusUser->delete();
        }
    }
}
