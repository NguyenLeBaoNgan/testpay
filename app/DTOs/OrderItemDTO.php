<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class OrderItemDTO extends Data
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public string $price,
        // public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: $data['quantity'],
            price: $data['price'] ?? '0',
            // name: $data['name'],
        );
    }
    public function getCartQuantity(): int
    {
        return $this->quantity;
    }
}
