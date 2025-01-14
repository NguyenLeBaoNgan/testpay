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
use App\Services\PaymentService;
use Illuminate\Http\Request;
use App\Models\Product;
use Exception;

class PaymentController extends Controller
{
    public function index()
    {
        // return Payment::all();
        return Payment::with('paymentDetails')->get();
    }
    protected $paymentService;
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(Request $request)
    {
        Log::info('Payment request received', [
            'order_id' => $request->order_id,
            'method' => $request->method,
            'payment_status' => $request->payment_status,
            'transaction_id' => $request->transaction_id,
        ]);
        try {
            Log::info('Payment processed successfully', ['order_id' => $request->order_id]);
            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json([
                    'error' => true,
                    'message' => 'Order not found'
                ], 404);
            }


            $totalAmount = $order->total_amount;

            $orderDTO = new OrderDTO(
                $order->id,
                $order->user_id,
                'unpaid',
                []
            );

            $paymentData = $request->all();

            $paymentDTO = new PaymentDTO(
                $paymentData['order_id'],
                $paymentData['method'],
                $paymentData['payment_status'],
                $totalAmount,
                $paymentData['transaction_id'] ?? null
            );


            $paymentDetailDTO = new PaymentDetailDTO(
                $paymentData['phone'],
                $paymentData['email'],
                $paymentData['address'],
                $paymentData['note'] ?? null
            );


            $payment = $this->paymentService->processPayment($paymentDTO, $paymentDetailDTO);
            $this->updateProductStock($order);
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment' => $payment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Payment processing error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProductStock($order)
    {
        foreach ($order->items as $orderItem) {
            $product = Product::find($orderItem->product_id);
            if ($product) {
                $product->decrement('quantity', $orderItem->quantity);
            }
            $product->save();
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
