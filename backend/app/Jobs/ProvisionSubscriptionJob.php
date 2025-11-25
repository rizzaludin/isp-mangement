<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\NetworkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function handle(NetworkService $networkService): void
    {
        Log::info("Starting provision for subscription {$this->subscription->id}");

        try {
            // Provision on MikroTik
            if ($this->subscription->router) {
                $networkService->queueProvisionJob($this->subscription);
            }

            // Provision on OLT
            if ($this->subscription->olt && $this->subscription->onu) {
                $networkService->queueOltProvisionJob($this->subscription);
            }

            // Update status
            $this->subscription->update(['status' => 'active']);
            $this->subscription->radiusUser->update(['status' => 'active']);

            Log::info("Provision completed for subscription {$this->subscription->id}");
        } catch (\Exception $e) {
            Log::error("Provision failed for subscription {$this->subscription->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Provision job failed for subscription {$this->subscription->id}: " . $exception->getMessage());
        
        $this->subscription->update([
            'status' => 'pending',
            'notes' => 'Provision failed: ' . $exception->getMessage(),
        ]);
    }
}
