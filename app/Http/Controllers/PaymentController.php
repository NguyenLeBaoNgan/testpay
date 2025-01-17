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
            'referenceCode' => $request->referenceCode,
        ]);

        try {
            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json([
                    'error' => true,
                    'message' => 'Order not found'
                ], 404);
            }

            $existingPayment = Payment::where('order_id', $request->order_id)->first();
            if ($existingPayment) {
                return response()->json([
                    'error' => true,
                    'message' => 'Payment already exists for this order'
                ], 400);
            }

            $totalAmount = $order->total_amount;

            // Create DTOs
            $paymentDTO = new PaymentDTO(
                $request->order_id,
                $request->method,
                $request->payment_status,
                $totalAmount,
                $request->referenceCode ?? null
            );

            $paymentDetailDTO = new PaymentDetailDTO(
                $request->phone,
                $request->email,
                $request->address,
                $request->note ?? null
            );

            // Process Payment
            $payment = $this->paymentService->processPayment($paymentDTO, $paymentDetailDTO);
            Log::info('Payment successfully created', ['payment_id' => $payment->id]);

            // Update product stock
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
