<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SePay\SePay\Pipelines\ProcessPaymentPipeline;
use SePay\SePay\Pipes\CreateTransactionPipe;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(ProcessPaymentPipeline::class, function ($app) {
        //     return new ProcessPaymentPipeline([
        //         new CreateTransactionPipe(),
        //     ]);
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \SePay\SePay\Events\SePayWebhookEvent::class,
            \App\Listeners\SePayWebhookListener::class,
        );
    }
}
