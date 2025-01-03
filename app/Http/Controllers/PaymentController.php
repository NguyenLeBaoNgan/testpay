<?php

namespace App\Http\Controllers;

use App\DTOs\OrderDTO;
use App\DTOs\PaymentDetailDTO;
use App\DTOs\PaymentDTO;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Stripe\StripeClient;
use App\Models\Payment;
use App\Models\PaymentDetails;

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

        $payment = new Payment([
            'order_id' => $paymentDTO->order_id,
            'amount' => $paymentDTO->payment_amount,
            'status' => $paymentDTO->payment_status,
            'payment_method' => $paymentDTO->method,
            'transaction_id' => $paymentDTO->transaction_id,

        ]);
        $payment->save();
        $paymentDetails = PaymentDetails::create([
            // 'id' => (string) Str::ulid(),
            'payment_id' => $payment->id,
            'address' => $paymentDetailDTO->address,
            'phone' => $paymentDetailDTO->phone,
            'email' => $paymentDetailDTO->email,
            'note' => $paymentDetailDTO->note,
        ]);
        Log::info('New Payment Created', [
            'payment' => $payment,
            'payment_details' => $paymentDetails
        ]);

        return response()->json($payment, 201);

        // $payment = new Payment([
        //     'order_id' => $paymentDTO->order_id,
        //     'amount' => $paymentDTO->payment_amount,
        //     'status' => 'pending',
        //     'payment_method' => $paymentDTO->method,
        // ]);


        // $payment->save();

        // $stripe = new StripeClient{('')};
        // $paymentIntent = $stripe->paymentIntents->create([
        //     'amount' => $paymentDTO->payment_amount,
        //     'currency' => 'usd',
        //     'payment_method_types' => ['card'],
        // ]);

        // $payment->transaction_id = $paymentIntent->id;
        // $payment->save();

    }
    public function cancelPayment($paymentId)
    {
        $payment = Payment::find($paymentId);

        if (!$payment || $payment->payments_status !== 'pending') {
            return response()->json(['error' => 'Payment not found or already processed'], 404);
        }

        $payment->update([
            'payments_status' => 'canceled',
        ]);

        Log::info('Payment canceled', ['payment_id' => $paymentId]);

        return response()->json(['message' => 'Payment canceled successfully'], 200);
    }
}
