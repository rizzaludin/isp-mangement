<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Payment;
use App\Observers\SubscriptionObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Subscription::observe(SubscriptionObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
