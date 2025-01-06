<?php

namespace App\Http\Controllers;

use App\DTOs\TransactionDTO;
use Illuminate\Http\Request;
use App\Models\Transaction;

use Illuminate\Support\Facades\Log;

class SePayWebhookController extends Controller
{
    public function webhook(Request $request)
    {

        $token = $request->header('Authorization');

        $expectedToken = 'Bearer c4e88037-a3d1-4b6d-ab63-a68fda287abb';

        Log::info('Received Authorization Token:', ['token' => $token]);
        Log::info('Expected Authorization Token:', ['expected_token' => $expectedToken]);


        if ($token !== $expectedToken) {
            Log::error('Unauthorized access to webhook', ['request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->all();
        Log::info('Received data:', ['data' => $data]);

        if ($data['transferType'] === 'in' || $data['transferType'] === 'out') {
            $transactionDTO = TransactionDTO::fromArray($data);

           
            Transaction::create([
                'gateway' => $transactionDTO->gateway,
                'transaction_date' => now(),
                'account_number' => $transactionDTO->accountNumber,
                'sub_account' => $transactionDTO->subAccount,
                'amount_in' => $transactionDTO->amountIn,
                'amount_out' => $transactionDTO->amountOut,
                'accumulated' => $transactionDTO->accumulated,
                'code' => $transactionDTO->code,
                'transaction_content' => $transactionDTO->transactionContent,
                'reference_number' => $transactionDTO->referenceNumber,
                'body' => $transactionDTO->body,
            ]);

            return response()->json(['success' => true, 'message' => 'Received payment']);
        }

        return response()->json(['success' => false, 'message' => 'Unhandled transfer type']);
    }
}
