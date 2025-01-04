<?php

namespace App\Http\Controllers;

use App\DTOs\OrderDTO;
use App\DTOs\PaymentDetailDTO;
use App\DTOs\PaymentDTO;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Stripe\StripeClient;
use App\Models\Payment;
use App\Models\PaymentDetails;
use Exception;

class PaymentController extends Controller
{
    public function index()
    {
        return Payment::all();
    }

    public function store(PaymentDTO $paymentDTO, PaymentDetailDTO $paymentDetailDTO)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $stripe = new StripeClient('sk_test_51QbecVCHpiBRB5pBrNnkZ78mhhQh4qzijkjUZ7cl4gKdrr19dZbfZyrPWIW6STjYtgRr7Uw3M7SlqVGWDfeEgsxc007BDUBsbg');

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' =>(int) $paymentDTO->payment_amount,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
            ]);

            $transactionId = $paymentIntent->id;

            $payment = new Payment([
                'order_id' => $paymentDTO->order_id,
                'payment_amount' => $paymentDTO->payment_amount,
                'status' => 'pending',
                'method' => $paymentDTO->method,
                'transaction_id' => $transactionId,
            ]);
            $payment->save();

            $paymentDetails = new PaymentDetails([
               'payment_id' => $payment->id,
                'address' => $paymentDetailDTO->address,
                'phone' => $paymentDetailDTO->phone,
                'email' => $paymentDetailDTO->email,
                'note' => $paymentDetailDTO->note,
            ]);
            $paymentDetails->save();
            Log::info('New Payment Created', [
                'payment' => $payment,
                'payment_details' => $paymentDetails,
            ]);
            if( $paymentIntent->status === 'succeeded'){
                $payment->update([
                    'status' => 'success',
                ]);
                $order = Order::find($paymentDTO->order_id);
                $order->update([
                    'status' => 'paid',
                ]);
                $order->save();
            } else if($paymentIntent->status === 'failed'){
                $payment->update([
                    'status' => 'failed',
                ]);
                $order = Order::find($paymentDTO->order_id);
                $order->update([
                    'status' => 'cancelled',
                ]);
                $order->save();
            }else
            {
                $payment->update([
                    'status' => 'pending',
                ]);
            }
            $payment->save();

            return response()->json($payment, 201);

        } catch (Exception $e) {
            Log::error(' Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment creation failed'], 500);
        }
    }


    // public function cancelPayment($paymentId)
    // {
    //     $payment = Payment::find($paymentId);

    //     if (!$payment || $payment->payments_status !== 'pending') {
    //         return response()->json(['error' => 'Payment not found or already processed'], 404);
    //     }

    //     $payment->update([
    //         'payments_status' => 'canceled',
    //     ]);

    //     Log::info('Payment canceled', ['payment_id' => $paymentId]);

    //     return response()->json(['message' => 'Payment canceled successfully'], 200);
    // }
}
