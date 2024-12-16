<?php
namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;

class OrderItemDTO extends Data
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public float $price

    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            price: $data['price']
        );
    }
}
