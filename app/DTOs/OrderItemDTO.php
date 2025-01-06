<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class OrderItemDTO extends Data
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public string $price
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            price: $data['price'] ?? '0'
        );
    }
    public function getCartQuantity(): int
    {
        return $this->quantity;
    }
}
