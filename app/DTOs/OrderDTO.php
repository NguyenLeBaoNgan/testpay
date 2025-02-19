<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class OrderDTO extends Data
{
    public function __construct(
        // public ?string $userId,
        public ?string $user_id,
        // public ?float $totalAmount,
        public ?string $totalAmount,
        public ?string $status,
        public ?array $items =[],
    ) {}
    // public static function fromArray(array $data, string $userId): self
    // {
    //     return new self(
    //         userId: $userId,
    //         totalAmount: $data['total_amount'],
    //         status: $data['status'] ?? 'pending',
    //         items: $data['items']
    //     );
    // }
}
