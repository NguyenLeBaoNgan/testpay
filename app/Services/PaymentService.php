<?php
namespace App\Services;

use App\Pipes\ValidateTransaction;
use App\Pipes\SaveTransactionToDatabase;
use App\Pipes\ProcessPaymentPipeline;
use App\Pipes\ProcessPaymentDetailPipe;
use Illuminate\Pipeline\Pipeline;
use App\DTOs\PaymentDetailDTO;
use App\DTOs\PaymentDTO;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\PaymentDetails;
use App\Models\Transaction;



class PaymentService
{
    public function processPayment(PaymentDTO $paymentDTO, PaymentDetailDTO $paymentDetailDTO)
    {
        if ($paymentDTO->transaction_id) {
            $existingPayment = Payment::where('transaction_id', $paymentDTO->transaction_id)->first();
            if ($existingPayment) {
                throw new \Exception('Transaction has already been processed.');
            }
        }

        $order = Order::find($paymentDTO->order_id);
        if (!$order) {
            throw new \Exception('Order not found.');
        }
        if (in_array($order->status, ['cancelled', 'refunded'])) {
            throw new \Exception('Cannot process payment for a cancelled or refunded order.');
        }

        $payment = Payment::create([
            'order_id' => $paymentDTO->order_id,
            'method' => $paymentDTO->method,
            'payment_status' => $paymentDTO->payment_status,
            'payment_amount' => $paymentDTO->payment_amount,
            'transaction_id' => $paymentDTO->transaction_id,
        ]);

        PaymentDetails::create([
            'payment_id' => $payment->id,
            'phone' => $paymentDetailDTO->phone,
            'email' => $paymentDetailDTO->email,
            'address' => $paymentDetailDTO->address,
            'note' => $paymentDetailDTO->note,
        ]);
        $order = Order::find($paymentDTO->order_id);

        switch ($paymentDTO->payment_status) {
            case 'succeeded':
                $payment->update(['status' => 'success']);
                $order->update(['status' => 'paid']);
                break;

            case 'failed':
                $payment->update(['status' => 'failed']);
                $order->update(['status' => 'cancelled']);
                break;

            case 'refunded':
                if ($order->status === 'paid') {
                    $payment->update(['status' => 'refunded']);
                    $order->update(['status' => 'refunded']);
                } else {
                    throw new \Exception('Cannot refund an order that has not been paid.');
                }
                break;

            default:
                $payment->update(['status' => 'pending']);
                break;
        }


        Log::info('Payment status after update: ' . $paymentDTO->payment_status);

        return $payment;
    }

    // public function refund(string $transactionId, float $refundAmount, string $reason)
    // {
    //     try {
    //         $transaction = Transaction::where('transaction_id', $transactionId)->first();
    //         if (!$transaction) {
    //             throw new \Exception('Transaction not found.');
    //         }

    //         $payment = Payment::where('transaction_id', $transaction->id)->first();
    //         if (!$payment) {
    //             throw new \Exception('Payment not found.');
    //         }

    //         $order = Order::find($payment->order_id);
    //         if (!$order) {
    //             throw new \Exception('Order not found.');
    //         }

    //         if ($order->status !== 'paid') {
    //             throw new \Exception('Cannot refund an order that has not been paid.');
    //         }

    //         if ($refundAmount > $payment->amount) {
    //             throw new \Exception('Refund amount exceeds the payment amount.');
    //         }

    //         $paymentGateway = resolve(PaymentService::class);
    //         $response = $paymentGateway->refund($transaction->transaction_id, $refundAmount, $reason);

    //         if (!$response['success']) {
    //             throw new \Exception('Refund failed: ' . $response['message']);
    //         }

    //         $payment->update([
    //             'status' => 'refunded',
    //             'refunded_amount' => $refundAmount
    //         ]);

    //         $order->update([
    //             'status' => 'refunded'
    //         ]);


    //         Log::info('Refund successful', [
    //             'transaction_id' => $transaction->transaction_id,
    //             'refund_amount' => $refundAmount,
    //             'reason' => $reason
    //         ]);

    //         return response()->json(['success' => true, 'message' => 'Refund successful.']);
    //     } catch (\Exception $e) {
    //         Log::error('Refund failed', [
    //             'transaction_id' => $transactionId,
    //             'error' => $e->getMessage()
    //         ]);

    //         return response()->json(['error' => $e->getMessage()], 400);
    //     }
    // }

}
