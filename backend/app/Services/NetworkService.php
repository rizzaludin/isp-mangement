<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\DeviceRouter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class NetworkService
{
    public function queueProvisionJob(Subscription $subscription): bool
    {
        try {
            $jobData = [
                'type' => 'mikrotik.provision',
                'data' => [
                    'router' => $subscription->router->getConnectionConfig(),
                    'username' => $subscription->username_pppoe,
                    'password' => $subscription->password_pppoe,
                    'rate_limit' => $subscription->service->rate_limit,
                    'subscription_id' => $subscription->id,
                ],
                'callback_key' => "provision_result_{$subscription->id}",
            ];

            Redis::rpush('network:jobs', json_encode($jobData));
            
            Log::info("Queued provision job for subscription {$subscription->id}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue provision job: " . $e->getMessage());
            return false;
        }
    }

    public function queueSuspendJob(Subscription $subscription): bool
    {
        try {
            $jobData = [
                'type' => 'mikrotik.suspend',
                'data' => [
                    'router' => $subscription->router->getConnectionConfig(),
                    'username' => $subscription->username_pppoe,
                    'subscription_id' => $subscription->id,
                ],
                'callback_key' => "suspend_result_{$subscription->id}",
            ];

            Redis::rpush('network:jobs', json_encode($jobData));
            
            Log::info("Queued suspend job for subscription {$subscription->id}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue suspend job: " . $e->getMessage());
            return false;
        }
    }

    public function queueUnsuspendJob(Subscription $subscription): bool
    {
        try {
            $jobData = [
                'type' => 'mikrotik.unsuspend',
                'data' => [
                    'router' => $subscription->router->getConnectionConfig(),
                    'username' => $subscription->username_pppoe,
                    'rate_limit' => $subscription->service->rate_limit,
                    'subscription_id' => $subscription->id,
                ],
                'callback_key' => "unsuspend_result_{$subscription->id}",
            ];

            Redis::rpush('network:jobs', json_encode($jobData));
            
            Log::info("Queued unsuspend job for subscription {$subscription->id}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue unsuspend job: " . $e->getMessage());
            return false;
        }
    }

    public function queueDisconnectJob(Subscription $subscription): bool
    {
        try {
            $jobData = [
                'type' => 'mikrotik.disconnect',
                'data' => [
                    'router' => $subscription->router->getConnectionConfig(),
                    'username' => $subscription->username_pppoe,
                    'subscription_id' => $subscription->id,
                ],
                'callback_key' => "disconnect_result_{$subscription->id}",
            ];

            Redis::rpush('network:jobs', json_encode($jobData));
            
            Log::info("Queued disconnect job for subscription {$subscription->id}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue disconnect job: " . $e->getMessage());
            return false;
        }
    }

    public function queueOltProvisionJob(Subscription $subscription): bool
    {
        if (!$subscription->olt || !$subscription->onu) {
            return false;
        }

        try {
            $jobData = [
                'type' => 'olt.provision',
                'data' => [
                    'olt' => $subscription->olt->getConnectionConfig(),
                    'serial_number' => $subscription->onu->serial_number,
                    'pon_port' => $subscription->onu->pon_port,
                    'onu_index' => $subscription->onu->onu_index,
                    'vlan_id' => $subscription->vlan_id,
                    'subscription_id' => $subscription->id,
                ],
                'callback_key' => "olt_provision_result_{$subscription->id}",
            ];

            Redis::rpush('network:jobs', json_encode($jobData));
            
            Log::info("Queued OLT provision job for subscription {$subscription->id}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to queue OLT provision job: " . $e->getMessage());
            return false;
        }
    }

    public function getJobResult(string $callbackKey, int $timeout = 30): ?array
    {
        $result = Redis::get($callbackKey);
        
        if ($result) {
            Redis::del($callbackKey);
            return json_decode($result, true);
        }
        
        return null;
    }

    public function testRouterConnection(DeviceRouter $router): array
    {
        try {
            $jobData = [
                'type' => 'mikrotik.test_connection',
                'data' => [
                    'router' => $router->getConnectionConfig(),
                ],
                'callback_key' => "test_connection_{$router->id}_" . time(),
            ];

            Redis::rpush('network:jobs', json_encode($jobData));
            
            sleep(2); // Wait for worker to process
            
            $result = $this->getJobResult($jobData['callback_key']);
            
            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Connection failed',
                'error' => $result['error'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
