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
use Carbon\Carbon;
use App\Models\Transaction;

class PaymentController extends Controller
{
    public function index()
    {
        // return Payment::all();
        return Payment::with('paymentDetails')->get();
    }
    public function show($id)
    {
        return Payment::with('paymentDetails')->find($id);
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

            // $existingPayment = Payment::where('order_id', $request->order_id)->first();
            // if ($existingPayment) {
            //     return response()->json([
            //         'error' => true,
            //         'message' => 'Payment already exists for this order'
            //     ], 400);
            // }
            $validMethods = ['bank_transfer', 'cash_on_delivery'];
            if (!in_array($request->method, $validMethods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method'
                ], 400);
            }
            $validPaymentStatuses = ['pending', 'completed', 'cancelled'];
            $paymentStatus = $request->payment_status ?? 'pending';
            if (!in_array($paymentStatus, $validPaymentStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment status'
                ], 400);
            }

            $existingPayment = Payment::where('order_id', $request->order_id)->first();
            if ($existingPayment) {
                $existingPayment->update([
                    'method' => $request->method,
                    'payment_status' => $paymentStatus,
                    'referenceCode' => $request->referenceCode,
                ]);
                //đồng bộ
                $this->syncOrderStatus($order, $existingPayment->payment_status);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment updated successfully',
                    'payment' => $existingPayment
                ]);
            }

            $totalAmount = $order->total_amount;

            // Create DTOs
            $paymentDTO = new PaymentDTO(
                $request->order_id,
                $request->method,
                $paymentStatus,
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


            $this->syncOrderStatus($order, $payment->payment_status??'pending');

            // Update product stock
            $this->updateProductStock($order);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment' => $payment,
                'transaction_id' => $payment->transaction_id
            ], 201);
        } catch (\Exception $e) {
            Log::error('Payment processing error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    private function syncOrderStatus(Order $order, string $paymentStatus): void
    {
        switch ($paymentStatus) {
            case 'pending':
                if ($order->status === 'unpaid') {
                $order->update(['status' => 'unpaid']);
                }
                break;
            case 'completed':
                $order->update(['status' => 'paid']);
                break;
            case 'cancelled':
                $order->update(['status' => 'cancelled']);
                break;
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
    public function getMonthlyRevenue(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);

        try {

            $monthlyRevenue = Payment::where('payments_status', 'completed')
                ->whereYear('created_at', $year)
                ->selectRaw('MONTH(created_at) as month, SUM(payment_amount) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            $monthlyTransactions = Transaction::whereYear('transaction_date', $year)
                ->selectRaw('
        MONTH(transaction_date) as month,
        COALESCE(SUM(amount_in), 0) as amount_in,
        COALESCE(SUM(amount_out), 0) as amount_out,
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN amount_in > 0 THEN 1 END) as count_in,
        COUNT(CASE WHEN amount_out > 0 THEN 1 END) as count_out
    ')
                ->groupBy('month')
                ->orderBy('month')
                ->get();


            return response()->json([
                'year' => $year,
                'monthly_revenue' => $monthlyRevenue,
                'monthly_transactions' => $monthlyTransactions->isEmpty() ? [] : $monthlyTransactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error calculating monthly revenue', 'message' => $e->getMessage()], 500);
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
