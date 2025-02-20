<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

class PaymentUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $transactionId;
    public $status;
    public function __construct($transactionId, $status)
    {
        $this->transactionId = $transactionId;
        $this->status = $status;
        Log::info(' Event PaymentUpdated đ', [
            'transaction_id' => $this->transactionId,
            'status' => $this->status
        ]);
    }

    public function broadcastOn()
    {
        Log::info("Đang phát event ", ['channel' => 'payments']);
        return new Channel('payments');
    }

    public function broadcastAs()
    {
        return 'payment.updated';
    }

    public function broadcastWith()
    {
        $data = [
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
        ];

        Log::info('\ Dữ liệu event được gửi qua broadcast', $data);

        return $data;
    }
}
