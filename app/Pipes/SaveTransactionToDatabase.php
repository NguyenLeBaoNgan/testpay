<?php

namespace App\Pipes;

use App\DTOs\TransactionDTO;
use App\Models\Transaction;

class SaveTransactionToDatabase
{
    public function handle(TransactionDTO $transactionDTO, \Closure $next)
    {
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

        return $next($transactionDTO);
    }
}
