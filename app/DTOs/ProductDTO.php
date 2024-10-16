<?php
namespace App\DTOs;

class ProductDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public float $price,
        public int $stock,
        public int $category_id,
        public int $user_id
    ) {}
}
