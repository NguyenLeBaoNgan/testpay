<?php

namespace App\Pipes;

use App\DTOs\TransactionDTO;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class SaveTransactionToDatabase
{
    public function handle(TransactionDTO $transactionDTO, \Closure $next)
    {
       Log::info('Transaction DTO', (array)$transactionDTO);
        if ($transactionDTO->transferType === 'in') {
            $transactionDTO->amountIn = $transactionDTO->amountIn;
            $transactionDTO->amountOut = 0;
        } else {
            $transactionDTO->amountOut = $transactionDTO->amountOut;
            //$transactionDTO->amountIn = 0;
        }
        if ($transactionDTO->amountOut === null) {
            $transactionDTO->amountOut = 0;
        }
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
            'body' => json_encode($transactionDTO),
        ]);
        Log::info('save', (array)$transactionDTO);
        return $next($transactionDTO);
    }
}
