<?php

namespace App\Pipes;

use App\DTOs\TransactionDTO;

class ValidateTransaction
{
    public function handle(TransactionDTO $transactionDTO, \Closure $next)
    {
        if (!$transactionDTO->gateway || !$transactionDTO->accountNumber) {
            throw new \Exception('Dữ liệu không hợp lệ: thiếu gateway hoặc accountNumber');
        }

        return $next($transactionDTO);
    }
}
