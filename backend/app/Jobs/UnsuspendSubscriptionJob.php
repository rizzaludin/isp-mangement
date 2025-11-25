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

class UnsuspendSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Subscription $subscription
    ) {}

    public function handle(NetworkService $networkService): void
    {
        Log::info("Starting unsuspend for subscription {$this->subscription->id}");

        try {
            if ($this->subscription->router) {
                $networkService->queueUnsuspendJob($this->subscription);
            }

            $this->subscription->update(['status' => 'active']);
            $this->subscription->radiusUser->update(['status' => 'active']);

            Log::info("Unsuspend completed for subscription {$this->subscription->id}");
        } catch (\Exception $e) {
            Log::error("Unsuspend failed for subscription {$this->subscription->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
