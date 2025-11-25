<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceRouter;
use App\Models\DeviceOlt;
use App\Models\DeviceOnu;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    /**
     * Get aggregated dashboard summary
     */
    public function summary()
    {
        $data = [
            // Customer stats
            'customers' => [
                'total' => Customer::count(),
                'active' => Customer::where('status', 'active')->count(),
                'suspended' => Customer::where('status', 'suspended')->count(),
                'new_this_month' => Customer::whereMonth('created_at', Carbon::now()->month)->count(),
            ],

            // Subscription stats
            'subscriptions' => [
                'total' => Subscription::count(),
                'active' => Subscription::where('status', 'active')->count(),
                'pending' => Subscription::where('status', 'pending')->count(),
                'suspended' => Subscription::where('status', 'suspended')->count(),
                'terminated' => Subscription::where('status', 'terminated')->count(),
            ],

            // Invoice stats
            'invoices' => [
                'total_this_month' => Invoice::whereMonth('created_at', Carbon::now()->month)->count(),
                'unpaid' => Invoice::where('status', 'unpaid')->count(),
                'overdue' => Invoice::where('status', 'overdue')->count(),
                'paid' => Invoice::where('status', 'paid')->count(),
                'total_unpaid_amount' => Invoice::whereIn('status', ['unpaid', 'overdue'])->sum('total'),
                'total_paid_amount' => Invoice::where('status', 'paid')
                    ->whereMonth('paid_at', Carbon::now()->month)
                    ->sum('total'),
            ],

            // Device stats
            'devices' => [
                'routers' => [
                    'total' => DeviceRouter::count(),
                    'active' => DeviceRouter::where('status', 'active')->count(),
                    'inactive' => DeviceRouter::where('status', 'inactive')->count(),
                    'maintenance' => DeviceRouter::where('status', 'maintenance')->count(),
                ],
                'olts' => [
                    'total' => DeviceOlt::count(),
                    'active' => DeviceOlt::where('status', 'active')->count(),
                    'inactive' => DeviceOlt::where('status', 'inactive')->count(),
                ],
                'onus' => [
                    'total' => DeviceOnu::count(),
                    'online' => DeviceOnu::where('status', 'online')->count(),
                    'offline' => DeviceOnu::where('status', 'offline')->count(),
                ],
            ],

            // Revenue stats
            'revenue' => [
                'this_month' => Invoice::where('status', 'paid')
                    ->whereMonth('paid_at', Carbon::now()->month)
                    ->sum('total'),
                'last_month' => Invoice::where('status', 'paid')
                    ->whereMonth('paid_at', Carbon::now()->subMonth()->month)
                    ->sum('total'),
                'this_year' => Invoice::where('status', 'paid')
                    ->whereYear('paid_at', Carbon::now()->year)
                    ->sum('total'),
            ],
        ];

        // Calculate growth
        $lastMonthRevenue = $data['revenue']['last_month'];
        $thisMonthRevenue = $data['revenue']['this_month'];
        
        if ($lastMonthRevenue > 0) {
            $data['revenue']['growth_percentage'] = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        } else {
            $data['revenue']['growth_percentage'] = 0;
        }

        return response()->json($data);
    }

    /**
     * Get device monitoring data
     */
    public function devices()
    {
        // Routers with simulated metrics
        $routers = DeviceRouter::select('id', 'name', 'ip_address', 'status', 'vendor')
            ->get()
            ->map(function ($router) {
                return [
                    'id' => $router->id,
                    'name' => $router->name,
                    'ip_address' => $router->ip_address,
                    'status' => $router->status,
                    'vendor' => $router->vendor,
                    'is_online' => $router->status === 'active' && rand(0, 100) > 10,
                    'cpu_usage' => rand(10, 80),
                    'memory_usage' => rand(30, 75),
                    'uptime_days' => rand(1, 180),
                    'active_users' => rand(5, 50),
                ];
            });

        // OLTs with simulated metrics
        $olts = DeviceOlt::select('id', 'name', 'ip_address', 'status', 'vendor', 'port_count')
            ->get()
            ->map(function ($olt) {
                $onuCount = DeviceOnu::where('olt_id', $olt->id)->count();
                $onuOnline = DeviceOnu::where('olt_id', $olt->id)->where('status', 'online')->count();
                
                return [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'ip_address' => $olt->ip_address,
                    'status' => $olt->status,
                    'vendor' => $olt->vendor,
                    'is_online' => $olt->status === 'active' && rand(0, 100) > 5,
                    'port_count' => $olt->port_count ?? 16,
                    'onu_count' => $onuCount,
                    'onu_online' => $onuOnline,
                    'onu_offline' => $onuCount - $onuOnline,
                ];
            });

        // ONUs summary
        $onuStats = [
            'total' => DeviceOnu::count(),
            'online' => DeviceOnu::where('status', 'online')->count(),
            'offline' => DeviceOnu::where('status', 'offline')->count(),
            'loss' => DeviceOnu::where('status', 'loss')->count(),
            'signal_issues' => DeviceOnu::where('signal_rx', '<', -27)->count(),
        ];

        return response()->json([
            'routers' => $routers,
            'olts' => $olts,
            'onu_stats' => $onuStats,
        ]);
    }

    /**
     * Get traffic monitoring data (simulated)
     */
    public function traffic()
    {
        // Simulate traffic data for last 24 hours
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = Carbon::now()->subHours($i);
            $data[] = [
                'time' => $hour->format('H:00'),
                'timestamp' => $hour->timestamp,
                'download_mbps' => rand(50, 200),
                'upload_mbps' => rand(20, 100),
                'active_users' => rand(50, 200),
            ];
        }

        return response()->json($data);
    }

    /**
     * Get recent alerts
     */
    public function alerts()
    {
        $alerts = [];

        // Check overdue invoices
        $overdueCount = Invoice::where('status', 'overdue')->count();
        if ($overdueCount > 0) {
            $alerts[] = [
                'id' => 'overdue_' . time(),
                'type' => 'warning',
                'severity' => 'medium',
                'title' => 'Overdue Invoices',
                'message' => "{$overdueCount} invoices are overdue",
                'action' => '/invoices?status=overdue',
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Check suspended subscriptions
        $suspendedCount = Subscription::where('status', 'suspended')->count();
        if ($suspendedCount > 0) {
            $alerts[] = [
                'id' => 'suspended_' . time(),
                'type' => 'info',
                'severity' => 'low',
                'title' => 'Suspended Subscriptions',
                'message' => "{$suspendedCount} subscriptions are currently suspended",
                'action' => '/subscriptions?status=suspended',
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Check ONUs with signal issues
        $signalIssues = DeviceOnu::where('signal_rx', '<', -27)->count();
        if ($signalIssues > 0) {
            $alerts[] = [
                'id' => 'signal_' . time(),
                'type' => 'error',
                'severity' => 'high',
                'title' => 'Signal Quality Issues',
                'message' => "{$signalIssues} ONUs have poor signal quality (< -27 dBm)",
                'action' => '/devices/onu?signal_quality=poor',
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Check pending subscriptions
        $pendingCount = Subscription::where('status', 'pending')->count();
        if ($pendingCount > 0) {
            $alerts[] = [
                'id' => 'pending_' . time(),
                'type' => 'info',
                'severity' => 'low',
                'title' => 'Pending Subscriptions',
                'message' => "{$pendingCount} subscriptions are waiting for provisioning",
                'action' => '/subscriptions?status=pending',
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Check inactive devices
        $inactiveRouters = DeviceRouter::where('status', 'inactive')->count();
        $inactiveOlts = DeviceOlt::where('status', 'inactive')->count();
        if ($inactiveRouters + $inactiveOlts > 0) {
            $alerts[] = [
                'id' => 'inactive_' . time(),
                'type' => 'warning',
                'severity' => 'medium',
                'title' => 'Inactive Devices',
                'message' => "{$inactiveRouters} routers and {$inactiveOlts} OLTs are inactive",
                'action' => '/devices/routers',
                'created_at' => Carbon::now()->toISOString(),
            ];
        }

        // Sort by severity
        usort($alerts, function ($a, $b) {
            $severity = ['high' => 0, 'medium' => 1, 'low' => 2];
            return $severity[$a['severity']] <=> $severity[$b['severity']];
        });

        return response()->json($alerts);
    }

    /**
     * Get chart data for dashboard
     */
    public function chartData(Request $request)
    {
        $type = $request->input('type', 'revenue');
        $period = $request->input('period', 'month'); // month, week, year

        switch ($type) {
            case 'revenue':
                return $this->getRevenueChartData($period);
            case 'subscriptions':
                return $this->getSubscriptionChartData($period);
            case 'customers':
                return $this->getCustomerChartData($period);
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }

    private function getRevenueChartData($period)
    {
        if ($period === 'month') {
            // Last 30 days
            $data = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $revenue = Invoice::where('status', 'paid')
                    ->whereDate('paid_at', $date->toDateString())
                    ->sum('total');
                
                $data[] = [
                    'date' => $date->format('M d'),
                    'value' => (float) $revenue,
                ];
            }
            return response()->json($data);
        }

        if ($period === 'year') {
            // Last 12 months
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $revenue = Invoice::where('status', 'paid')
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum('total');
                
                $data[] = [
                    'date' => $month->format('M Y'),
                    'value' => (float) $revenue,
                ];
            }
            return response()->json($data);
        }

        return response()->json([]);
    }

    private function getSubscriptionChartData($period)
    {
        // Subscription status distribution
        $data = [
            ['status' => 'Active', 'count' => Subscription::where('status', 'active')->count()],
            ['status' => 'Pending', 'count' => Subscription::where('status', 'pending')->count()],
            ['status' => 'Suspended', 'count' => Subscription::where('status', 'suspended')->count()],
            ['status' => 'Terminated', 'count' => Subscription::where('status', 'terminated')->count()],
        ];

        return response()->json($data);
    }

    private function getCustomerChartData($period)
    {
        if ($period === 'month') {
            // New customers last 30 days
            $data = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = Customer::whereDate('created_at', $date->toDateString())->count();
                
                $data[] = [
                    'date' => $date->format('M d'),
                    'value' => $count,
                ];
            }
            return response()->json($data);
        }

        return response()->json([]);
    }

    /**
     * Get recent activity feed
     */
    public function recentActivities()
    {
        $activities = [];

        // Recent subscriptions
        $recentSubs = Subscription::with(['customer', 'service'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentSubs as $sub) {
            $activities[] = [
                'id' => 'sub_' . $sub->id,
                'type' => 'subscription',
                'icon' => 'wifi',
                'title' => 'New Subscription',
                'description' => "{$sub->customer->name} subscribed to {$sub->service->name}",
                'link' => "/subscriptions/{$sub->id}",
                'created_at' => $sub->created_at->toISOString(),
            ];
        }

        // Recent payments
        $recentPayments = \App\Models\Payment::with(['invoice.customer'])
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentPayments as $payment) {
            $activities[] = [
                'id' => 'payment_' . $payment->id,
                'type' => 'payment',
                'icon' => 'credit-card',
                'title' => 'Payment Received',
                'description' => "{$payment->invoice->customer->name} paid Rp " . number_format($payment->amount),
                'link' => "/invoices/{$payment->invoice_id}",
                'created_at' => $payment->paid_at->toISOString(),
            ];
        }

        // Sort by date
        usort($activities, function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return response()->json(array_slice($activities, 0, 10));
    }
}
