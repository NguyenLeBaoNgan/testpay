<?php
namespace App\Pipes;

use App\DTOs\PaymentDTO;
use App\Models\Payment;
use Closure;

class ProcessPaymentPipeline
{
    public function handle(PaymentDTO $paymentDTO, Closure $next)
    {
        $payment = Payment::create([
            'order_id' => $paymentDTO->order_id,
            'method' => $paymentDTO->method,
            'payments_status' => 'pending',
            'payment_amount' => $paymentDTO->payment_amount,
            'transaction_id' => $paymentDTO->transaction_id,
        ]);

        return $next($payment);
    }
}
