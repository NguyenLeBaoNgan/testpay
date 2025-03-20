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
        return Payment::with('paymentDetails')
        ->orderBy('created_at','desc')
        ->get();
    }
    public function show($id)
    {
        return Payment::with('paymentDetails')->find($id);
    }
    protected $paymentService;
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->middleware('role:admin')->only('update','destroy');
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
                $existingPayment->refresh();
                $this->syncOrderStatus($order, $existingPayment->payment_status ??'pending');

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
                case 'failed':
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
    public function cancel($order_id)
{
    try {
        $order = Order::find($order_id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $payment = Payment::where('order_id', $order_id)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        Log::info('Attempting to cancel payment', [
            'order_id' => $order_id,
            'method' => $payment->method,
            'payment_status' => $payment->payments_status,
        ]);

        if ($payment->payments_status !== 'pending' || $payment->method !== 'cash_on_delivery') {
            Log::info('Cancellation rejected', [
                'order_id' => $order_id,
                'reason' => 'Not pending or not COD'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel this payment. Only pending COD payments can be cancelled.'
            ], 400);
        }

        foreach ($order->items as $orderItem) {
            $product = Product::find($orderItem->product_id);
            if ($product) {
                $product->increment('quantity', $orderItem->quantity);
                Log::info("Updated product stock", [
                    'product_id' => $product->id,
                    'new_quantity' => $product->quantity
                ]);
            } else {
                Log::error("Product not found", ['product_id' => $orderItem->product_id]);
            }
        }
        $payment->update(['payments_status' => 'cancelled']);
        $payment->refresh(); // Làm mới để lấy giá trị mới từ database
        Log::info('Payment status updated', [
            'order_id' => $order_id,
            'new_payment_status' => $payment->payments_status,
        ]);

        $this->syncOrderStatus($order, 'cancelled');
        Log::info('Order status synced', [
            'order_id' => $order_id,
            'new_order_status' => $order->status,
        ]);

        Log::info('Payment cancelled successfully', [
            'order_id' => $order_id,
            'payment_status' => $payment->payments_status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment and order cancelled successfully'
        ]);
    } catch (\Exception $e) {
        Log::error('Error cancelling payment', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel payment: ' . $e->getMessage()
        ], 500);
    }
}

public function update(Request $request, $id)
{
    try {
        $payment = Payment::findOrFail($id);
        $validPaymentStatuses = ['pending', 'completed', 'cancelled', 'failed'];

        $validated = $request->validate([
            'payment_status' => 'required|in:' . implode(',', $validPaymentStatuses),
        ]);

        $payment->update([
            'payments_status' => $validated['payment_status'], // Sửa tên cột
            'updated_at' => Carbon::now(),
        ]);

        $payment->refresh();

        $order = Order::find($payment->order_id);
        if ($order) {
            $this->syncOrderStatus($order, $validated['payment_status']);
        }

        Log::info('Payment status updated', [
            'payment_id' => $payment->id,
            'new_status' => $validated['payment_status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
            'payment' => $payment->load('paymentDetails'),
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => true,
            'message' => $e->validator->errors()->first(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Payment status update failed', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function destroy($id)
{
    try {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
        ], 200);
    } catch (\Exception $e) {
        Log::error('Payment deletion failed', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}
}
