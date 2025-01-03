<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;

class PaymentDTO extends Data
{
    public function __construct(

        public ?string $order_id,

        public ?string $method,

        public ?string $payment_status,

        public ?string $payment_amount,

        public ?string $transaction_id = null,

        // public ?string $address,
        // public ?string $phone,
        // public ?string $email,

    ) {}
}
