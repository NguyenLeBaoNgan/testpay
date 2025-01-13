<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class DeleteUnpaidOrders extends Command
{
    protected $signature = 'orders:delete-unpaid';
    protected $description = 'Delete unpaid orders older than 24 hours';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $deletedOrders = Order::where('status', 'unpaid')
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        foreach ($deletedOrders as $order) {
            $order->items()->delete();
            $order->delete();
            Log::info("Deleted expired order: {$order->id}");
        }

        $this->info('Deleted unpaid orders older than 24 hours.');
    }
}
