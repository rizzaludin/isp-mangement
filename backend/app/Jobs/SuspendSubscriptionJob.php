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

class SuspendSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function handle(NetworkService $networkService): void
    {
        Log::info("Starting suspend for subscription {$this->subscription->id}");

        try {
            if ($this->subscription->router) {
                $networkService->queueSuspendJob($this->subscription);
            }

            $this->subscription->update(['status' => 'suspended']);
            $this->subscription->radiusUser->update(['status' => 'suspended']);

            Log::info("Suspend completed for subscription {$this->subscription->id}");
        } catch (\Exception $e) {
            Log::error("Suspend failed for subscription {$this->subscription->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
