<?php
namespace App\Pipes;

use App\DTOs\PaymentDetailDTO;
use App\Models\PaymentDetails;
use Closure;

class ProcessPaymentDetailPipe
{
    public function handle(PaymentDetailDTO $paymentDetailDTO, Closure $next)
    {
        $paymentDetail = PaymentDetails::create([
            //'payment_id' => $paymentDetailDTO->payment_id,
            'phone' => $paymentDetailDTO->phone,
            'email' => $paymentDetailDTO->email,
            'address' => $paymentDetailDTO->address,
            'note' => $paymentDetailDTO->note,
        ]);

        return $next($paymentDetail);
    }
}
