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
            Log::info('content', ['content' => $data['content']]);
            // Lấy order_id từ content
            $orderId = $this->extractOrderId($content);

            if (!$transactionId || !$orderId || !$status) {
                Log::error("Missing transaction ID or Order ID", ['transaction_id' => $transactionId, 'order_id' => $orderId, 'status' => $status]);
                return response()->json(['error' => 'Transaction ID or Order ID is missing'], 400);
            }

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
                $payment->status = $status === 'completed' ? 'paid' : 'failed';
                $payment->save();

                $order = $payment->order;
            if ($order) {
                $order->status = $status === 'completed' ? 'completed' : 'canceled'; // Cập nhật trạng thái đơn hàng
                $order->save();
            }
                Log::info('Payment updated with transaction ID', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id,
                    'referenceCode' => $payment->reference_number,
                    'status' => $payment->status,

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


    // public function webhook(Request $request)
    // {

    //     // $token = $request->header('Authorization');

    //     // $expectedToken = 'Bearer  ';

    //     // Log::info('Received Authorization Token:', ['token' => $token]);
    //     // Log::info('Expected Authorization Token:', ['expected_token' => $expectedToken]);


    //     // if ($token !== $expectedToken) {
    //     //     Log::error('Unauthorized access to webhook', ['request' => $request->all()]);
    //     //     return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    //     // }

    //     $data = $request->all();
    //     Log::info($request->all());
    //     // Log::info('Received data:', ['data' => $data]);

    //     if ($data['transferType'] === 'in' || $data['transferType'] === 'out') {
    //         $transactionDTO = TransactionDTO::fromArray($data);


    //         Transaction::create([
    //             'gateway' => $transactionDTO->gateway,
    //             'transaction_date' => now(),
    //             'account_number' => $transactionDTO->accountNumber,
    //             'sub_account' => $transactionDTO->subAccount,
    //             'amount_in' => $transactionDTO->amountIn,
    //             'amount_out' => $transactionDTO->amountOut,
    //             'accumulated' => $transactionDTO->accumulated,
    //             'code' => $transactionDTO->code,
    //             'transaction_content' => $transactionDTO->transactionContent,
    //             'reference_number' => $transactionDTO->referenceNumber,
    //             'body' => $transactionDTO->body,
    //         ]);

    //         return response()->json(['success' => true, 'message' => 'Received payment']);
    //     }

    //     return response()->json(['success' => false, 'message' => 'Unhandled transfer type']);
    // }
}
