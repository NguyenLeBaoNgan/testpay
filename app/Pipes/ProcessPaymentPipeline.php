<?php
namespace App\Pipes;

use App\DTOs\PaymentDTO;
use App\Models\Payment;
use Closure;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Transaction;

class ProcessPaymentPipeline
{
    public function handle(PaymentDTO $paymentDTO, Closure $next, TransactionDTO $transactionDTO)
    {

        $order = Order::where('id', $paymentDTO->order_id)->first();
        Log::info('Creating payment record', ['order_id' => $order->id]);
        // if (!$order) {
        //     Log::error('Order not found', ['order_id' => $paymentDTO->id]);
        //     throw new \Exception('Order not found.');
        // }
        if (empty($paymentDTO->transaction_id) && !empty($transactionDTO->referenceNumber)) {
            $paymentDTO->transaction_id = $transactionDTO->referenceNumber;
        }
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => $paymentDTO->method,
            'payment_status' => 'pending',
            'payment_amount' => $paymentDTO->payment_amount,
            'transaction_id' => $paymentDTO->transaction_id??null,
        ]);
        Log::info('Payment record created', ['payment_id' => $payment->id]);
        return $next($payment);
    }
}
