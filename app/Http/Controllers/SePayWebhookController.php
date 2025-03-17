<?php

namespace App\Http\Controllers;

use App\DTOs\TransactionDTO;
use Illuminate\Http\Request;
use App\Models\Transaction;

use Illuminate\Support\Facades\Log;
use App\Services\TransactionPipeline;
use App\Pipes\ValidateTransaction;
use App\Pipes\SaveTransactionToDatabase;
use Illuminate\Pipeline\Pipeline;
use App\Models\Payment;
use App\Events\PaymentUpdated;
use Illuminate\Support\Facades\Event;
use App\Models\Order;

class SePayWebhookController extends Controller
{
    public function index()
    {
        return Transaction::orderBy('created_at', 'desc')->get();
    }

    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            $transactionId = $data['referenceCode'] ?? null; // ID giao dịch từ webhook
            $content = $data['content'] ?? null;
            $status = $data['status'] ?? 'pending';

            Log::info('Webhook received', $request->all());

            // Lấy order_id từ content
            $orderId = $this->extractOrderId($content);
            Log::info('content', ['content' => $data['content']]);
            if (!$transactionId || !$orderId) {
                Log::error("Missing transaction ID or Order ID", ['transaction_id' => $transactionId, 'order_id' => $orderId, 'status' => $status]);
                return response()->json(['error' => 'Transaction ID or Order ID is missing'], 400);
            }
            $paymentSuccess = $this->isPaymentSuccessful($content, $status, $orderId);
            $transactionStatus = $paymentSuccess ? 'completed' : 'pending';
            Log::info('Payment success determined', [
                'payment_success' => $paymentSuccess,
                'transaction_status' => $transactionStatus
            ]);
            // Tạo transaction mới
            $transaction = Transaction::create([
                'transaction_id' => $transactionId,
                'gateway' => $data['gateway'],
                'account_number' => $data['accountNumber'],
                'transferType' => $data['transferType'],
                'amount_in' => $data['transferAmount'] ?? 0,
                'transactionContent' => $content,
                'status' => $transactionStatus,
            ]);

            // Cập nhật transaction_id trong bảng payments
            $payment = Payment::where('order_id', $orderId)->first();
            if ($payment) {
                $payment->transaction_id = $transaction->id;
                $payment->reference_number  = $transactionId;
                $payment->payments_status = $paymentSuccess  ? 'completed' : 'failed';
                $payment->save();

                $order = $payment->order;
                if ($order) {
                    $order->status = $paymentSuccess   ? 'paid' : 'unpaid';
                    $order->save();
                }
                Log::info('Payment updated with transaction ID', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id,
                    'referenceCode' => $payment->reference_number,
                    'status' => $payment->payments_status,

                ]);
            } else {
                Log::error("Payment not found for order_id", ['order_id' => $orderId]);
            }
            broadcast(new PaymentUpdated($transactionId, $transactionStatus ? 'completed' : 'cancelled'));
            Event::listen(PaymentUpdated::class, function ($event) {
                Log::info("🔥 Event PaymentUpdated được gửi đi", ['data' => $event->data]);
            });
            return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (\Exception $e) {
            Log::error("Error processing webhook", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error processing webhook'], 500);
        }
    }

    // Hàm để lấy order_id từ nội dung
    private function extractOrderId($content)
    {
        if (!$content) {
            return null;
        }

        // Tìm kiếm "DH" theo sau là chuỗi ký tự, không có dấu "-"
        if (preg_match('/DH([A-Za-z0-9]+)/', $content, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }

    private function isPaymentSuccessful($content, $status, $orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }
        if ($status === 'completed') {
            return true;
        }

        $transferAmount = $data['transferAmount'] ?? 0;
        if ($transferAmount >= $order->total_amount) {
            return true;
        }
        if ($content) {
            $keywords = ['CHUYEN TIEN', 'PAYMENT', 'THANH TOAN'];
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
    //     public function refund(TransactionDTO $transactionDTO)
    // {
    //     try {
    //         // Lấy thông tin giao dịch từ DTO
    //         $transactionId = $transactionDTO->transaction_id;
    //         $refundAmount = $transactionDTO->refund_amount;
    //         $reason = $transactionDTO->reason;

    //         // Tìm giao dịch bằng ULID
    //         $transaction = Transaction::where('transaction_ulid', $transactionId)->firstOrFail();

    //         // Kiểm tra trạng thái giao dịch
    //         if ($transaction->status !== 'success') {
    //             return response()->json(['error' => 'Transaction not eligible for refund'], 400);
    //         }

    //         // Kiểm tra số tiền hoàn lại
    //         if ($refundAmount > $transaction->amount_in) {
    //             return response()->json(['error' => 'Refund amount exceeds transaction amount'], 400);
    //         }

    //         // Lấy thông tin tài khoản khách hàng từ bảng Account
    //         $account = $transaction->account;
    //         if (!$account) {
    //             return response()->json(['error' => 'Customer account not found'], 404);
    //         }

    //         // Gửi yêu cầu hoàn tiền tới cổng thanh toán
    //         $response = $this->paymentGateway->refund(
    //             $transaction->transaction_ulid,
    //             $refundAmount,
    //             $reason,
    //             $account->account_number
    //         );

    //         if ($response['success']) {

    //             $transaction->refund_status = 'success';
    //             $transaction->refunded_amount = $refundAmount;
    //             $transaction->save();


    //             Log::info("Refund successful", [
    //                 'transaction_id' => $transaction->transaction_ulid,
    //                 'refund_amount' => $refundAmount,
    //                 'reason' => $reason,
    //                 'customer_account' => $account->account_number
    //             ]);

    //             return response()->json(['success' => true, 'message' => 'Refund successful']);
    //         } else {

    //             Log::error("Refund failed", [
    //                 'transaction_id' => $transaction->transaction_ulid,
    //                 'error' => $response['message']
    //             ]);

    //             return response()->json(['error' => 'Refund failed: ' . $response['message']], 400);
    //         }
    //     } catch (\Exception $e) {

    //         Log::error("Error during refund process", [
    //             'error' => $e->getMessage()
    //         ]);

    //         return response()->json(['error' => 'Error processing refund'], 500);
    //     }
    // }


}
