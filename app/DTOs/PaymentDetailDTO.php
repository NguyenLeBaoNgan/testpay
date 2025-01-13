<?php
namespace App\DTOs;
use Spatie\LaravelData\Data;


class PaymentDetailDTO extends Data
{
    public function __construct(
        // public string $payment_id,
        public string $phone,
        public string $email,
        public string $address,
        public ?string $note,
    ) {}
}
