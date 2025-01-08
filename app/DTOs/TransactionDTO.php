<?php

namespace App\DTOs;

class TransactionDTO
{
    public $gateway;
    public $transactionDate;
    public $accountNumber;
    public $subAccount;
    public $amountIn;
    public $amountOut;
    public $accumulated;
    public $code;
    public $transactionContent;
    public $referenceNumber;
    public $body;

    public $transferType;
    // public $transferAmount;
    public static function fromArray(array $data): self
    {
        $dto = new self();

        $dto->gateway = $data['gateway'] ?? null;
        $dto->transactionDate = $data['transactionDate'] ?? null;
        $dto->accountNumber = $data['accountNumber'] ?? null;
        $dto->subAccount = $data['subAccount'] ?? null;
        $dto->amountIn = $data['transferType'] === 'in' ? $data['transferAmount'] : 0;
        $dto->amountOut = $data['transferType'] === 'out' ? $data['transferAmount'] : 0;
        $dto->accumulated = $data['accumulated'] ?? 0;
        $dto->code = $data['code'] ?? null;
        $dto->transactionContent = $data['transactionContent'] ?? null;
        $dto->referenceNumber = $data['referenceNumber'] ?? null;
        $dto->body = $data['body'] ?? null;

        return $dto;
    }
}
