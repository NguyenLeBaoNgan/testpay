<?php

namespace App\DTOs;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class ProductDTO extends Data
{
    public function __construct(

        public ?string $name,

        public ?string $description,

        #[Nullable, Min(0)]
        public ?float $price,

        #[Nullable, Min(0)]
        public ?int $quantity,


        public ?string $category_id,


        public ?string $user_id,
    ) {}
}
