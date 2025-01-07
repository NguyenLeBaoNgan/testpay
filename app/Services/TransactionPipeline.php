<?php

namespace App\Services;

use Illuminate\Pipeline\Pipeline;

class TransactionPipeline
{
    public static function process($transactionDTO)
    {
        return app(Pipeline::class)
            ->send($transactionDTO)
            ->through([
                \App\Pipes\ValidateTransaction::class,
                \App\Pipes\SaveTransactionToDatabase::class,
            ])
            ->thenReturn();
    }
}
