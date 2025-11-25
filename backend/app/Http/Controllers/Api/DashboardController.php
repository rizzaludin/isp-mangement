<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RadiusSession;
use App\Models\DeviceRouter;
use App\Models\DeviceOlt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $stats = [
            // Customer Statistics
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'new_customers_this_month' => Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),

            // Subscription Statistics
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'pending_subscriptions' => Subscription::where('status', 'pending')->count(),
            'suspended_subscriptions' => Subscription::where('status', 'suspended')->count(),
            
            // Online Sessions
            'online_subscriptions' => RadiusSession::whereNull('stop_time')
                ->distinct('radius_user_id')
                ->count('radius_user_id'),

            // Invoice Statistics
            'total_invoices' => Invoice::count(),
            'unpaid_invoices' => Invoice::whereIn('status', ['unpaid', 'overdue'])->count(),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
            'unpaid_amount' => Invoice::whereIn('status', ['unpaid', 'overdue'])->sum('total'),

            // Revenue Statistics
            'monthly_revenue' => Payment::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
            'today_revenue' => Payment::whereDate('paid_at', today())->sum('amount'),
            'yearly_revenue' => Payment::whereYear('paid_at', now()->year)->sum('amount'),

            // Device Statistics
            'total_routers' => DeviceRouter::count(),
            'active_routers' => DeviceRouter::where('status', 'active')->count(),
            'total_olts' => DeviceOlt::count(),
            'active_olts' => DeviceOlt::where('status', 'active')->count(),

            // Growth Indicators
            'customer_growth_percentage' => $this->calculateGrowthPercentage(
                Customer::whereMonth('created_at', now()->month)->count(),
                Customer::whereMonth('created_at', now()->subMonth()->month)->count()
            ),
            'revenue_growth_percentage' => $this->calculateGrowthPercentage(
                Payment::whereMonth('paid_at', now()->month)->sum('amount'),
                Payment::whereMonth('paid_at', now()->subMonth()->month)->sum('amount')
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function recentActivities(): JsonResponse
    {
        $activities = [];

        // Recent Customers
        $recentCustomers = Customer::latest()
            ->take(5)
            ->get(['id', 'name', 'code', 'created_at']);

        foreach ($recentCustomers as $customer) {
            $activities[] = [
                'type' => 'customer_created',
                'title' => 'New Customer',
                'description' => "Customer {$customer->name} ({$customer->code}) was created",
                'timestamp' => $customer->created_at,
                'link' => "/customers/{$customer->id}",
            ];
        }

        // Recent Subscriptions
        $recentSubscriptions = Subscription::with(['customer', 'service'])
            ->latest()
            ->take(5)
            ->get();

        foreach ($recentSubscriptions as $sub) {
            $activities[] = [
                'type' => 'subscription_created',
                'title' => 'New Subscription',
                'description' => "{$sub->customer->name} subscribed to {$sub->service->name}",
                'timestamp' => $sub->created_at,
                'link' => "/subscriptions/{$sub->id}",
            ];
        }

        // Recent Payments
        $recentPayments = Payment::with(['customer', 'invoice'])
            ->latest('paid_at')
            ->take(5)
            ->get();

        foreach ($recentPayments as $payment) {
            $activities[] = [
                'type' => 'payment_received',
                'title' => 'Payment Received',
                'description' => "Payment of Rp " . number_format($payment->amount, 0, ',', '.') . " from {$payment->customer->name}",
                'timestamp' => $payment->paid_at,
                'link' => "/payments/{$payment->id}",
            ];
        }

        // Sort by timestamp desc
        usort($activities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        // Return top 20
        $activities = array_slice($activities, 0, 20);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    public function chartData(): JsonResponse
    {
        // Revenue chart - last 12 months
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $amount = Payment::whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');
            
            $revenueData[] = [
                'month' => $date->format('M Y'),
                'amount' => $amount,
            ];
        }

        // Subscription growth - last 12 months
        $subscriptionData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Subscription::where('status', 'active')
                ->where('created_at', '<=', $date->endOfMonth())
                ->count();
            
            $subscriptionData[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => $revenueData,
                'subscriptions' => $subscriptionData,
            ],
        ]);
    }

    private function calculateGrowthPercentage($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
