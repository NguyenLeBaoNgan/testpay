<?php
namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
class ProductDTO extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        public ?string $description,

        #[Required]
        public float $price,

        #[Required]
        public int $stock,

        #[Required]
        public int $category_id,

        #[Required]
        public int $user_id
    ) {}
}
