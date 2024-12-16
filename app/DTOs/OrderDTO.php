<?php
namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;

class OrderDTO extends Data
{
    public function __construct(

        public ?string $userId,

        public ?float $totalAmount,

        public ?string $status,

        public array $items

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
