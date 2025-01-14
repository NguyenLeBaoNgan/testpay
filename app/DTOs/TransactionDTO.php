<?php

namespace App\DTOs;

class TransactionDTO
{
    public ?string $gateway;
    public ?string $transactionDate;
    public ?string $accountNumber;
    public ?string $subAccount;
    public ?int $amountIn = 0;
    public ?int $amountOut = 0;
    public ?int $accumulated = 0;
    public ?string $code;
    public ?string $transactionContent;
    public ?string $referenceNumber;
    public ?string $body;
    public ?string $transferType;
    public ?string $status = 'pending';

    public static function fromArray(array $data): self
    {
        $dto = new self();

        $dto->gateway = $data['gateway'] ?? null;
        if (!$dto->gateway) {
            throw new \Exception('Dữ liệu không hợp lệ: thiếu gateway');
        }

        $dto->accountNumber = $data['accountNumber'] ?? null;
        if (!$dto->accountNumber) {
            throw new \Exception('Dữ liệu không hợp lệ: thiếu accountNumber');
        }

        $dto->transferType = $data['transferType'] ?? null;
        if (!$dto->transferType) {
            throw new \Exception('Dữ liệu không hợp lệ: thiếu transferType');
        }

        if ($dto->transferType === 'in') {
            $dto->amountIn = $data['transferAmount'] ?? 0;
            $dto->amountOut = 0;
        } elseif ($dto->transferType === 'out') {
            $dto->amountOut = $data['transferAmount'] ?? 0;
            $dto->amountIn = 0;
        }

        $dto->subAccount = $data['subAccount'] ?? null;
        $dto->accumulated = $data['accumulated'] ?? 0;
        $dto->code = $data['code'] ?? null;
        $dto->transactionContent = $data['content'] ?? null;
        $dto->referenceNumber = $data['referenceCode'] ?? null;
        $dto->body = $data['description'] ?? null;

        return $dto;
    }
}
