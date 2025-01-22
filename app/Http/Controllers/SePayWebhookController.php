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

class SePayWebhookController extends Controller
{
    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            $transactionId = $data['referenceCode'] ?? null; // ID giao dịch từ webhook
            $content = $data['content'] ?? null;
            $status = $data['status'] ?? null;

            Log::info('Webhook received', $request->all());

            // Lấy order_id từ content
            $orderId = $this->extractOrderId($content);
            Log::info('content', ['content' => $data['content']]);
            if (!$transactionId || !$orderId ) {
                Log::error("Missing transaction ID or Order ID", ['transaction_id' => $transactionId, 'order_id' => $orderId, 'status' => $status]);
                return response()->json(['error' => 'Transaction ID or Order ID is missing'], 400);
            }
            $paymentSuccess = $this->isPaymentSuccessful($content, $status);
            $transactionStatus = $paymentSuccess ? 'completed' : 'pending';
            // Tạo transaction mới
            $transaction = Transaction::create([
                'transaction_id' => $transactionId,
                'gateway' => $data['gateway'],
                'accountNumber' => $data['accountNumber'],
                'transferType' => $data['transferType'],
                'amount_in' => $data['transferAmount'] ?? 0,
                'transactionContent' => $content,
                'status' => $status,
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
                $order->status = $paymentSuccess   ? 'paid' : 'cancelled'; // Cập nhật trạng thái đơn hàng
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

        // Giả sử order_id nằm giữa chuỗi "-DH" và "-"
        if (preg_match('/-DH(.*?)\-/', $content, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }

    private function isPaymentSuccessful($content, $status)
    {

        if ($status === 'completed') {
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


    // public function refund(Request $request)
    // {
    //     try {
    //         $transactionId = $request->input('transaction_id');
    //         $refundAmount = $request->input('refund_amount');
    //         $reason = $request->input('reason');

    //         // Tìm giao dịch
    //         $transaction = Transaction::findOrFail($transactionId);

    //         // Kiểm tra trạng thái giao dịch
    //         if ($transaction->status !== 'success') {
    //             return response()->json(['error' => 'Transaction not eligible for refund'], 400);
    //         }

    //         // Kiểm tra số tiền hoàn lại
    //         if ($refundAmount > $transaction->amount_in) {
    //             return response()->json(['error' => 'Refund amount exceeds transaction amount'], 400);
    //         }

    //         // Gửi yêu cầu hoàn tiền tới cổng thanh toán
    //         $response = $this->paymentGateway->refund($transaction->transaction_id, $refundAmount, $reason);

    //         if ($response['success']) {
    //             // Cập nhật giao dịch với trạng thái hoàn tiền
    //             $transaction->refund_status = 'success';
    //             $transaction->refunded_amount = $refundAmount;
    //             $transaction->save();

    //             // Ghi log hoàn tiền thành công
    //             Log::info("Refund successful", [
    //                 'transaction_id' => $transaction->id,
    //                 'refund_amount' => $refundAmount,
    //                 'reason' => $reason
    //             ]);

    //             return response()->json(['success' => true, 'message' => 'Refund successful']);
    //         } else {
    //             // Ghi log hoàn tiền thất bại
    //             Log::error("Refund failed", [
    //                 'transaction_id' => $transaction->id,
    //                 'error' => $response['message']
    //             ]);

    //             return response()->json(['error' => 'Refund failed: ' . $response['message']], 400);
    //         }
    //     } catch (\Exception $e) {
    //         // Ghi log lỗi hệ thống
    //         Log::error("Error during refund process", [
    //             'error' => $e->getMessage()
    //         ]);

    //         return response()->json(['error' => 'Error processing refund'], 500);
    //     }
    // }

}
