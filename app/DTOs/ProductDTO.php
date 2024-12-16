<?php

namespace App\DTOs;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\ArrayType;

class ProductDTO extends Data
{
    public function __construct(

        public ?string $name,

        public ?string $description,

        #[Nullable, Min(0)]
        public ?float $price,

        #[Nullable, Min(0)]
        public ?int $quantity,

        #[ArrayType]
        public ?array $category_id = [],


        public ?string $user_id,


        public ?UploadedFile  $image,
    ) {
        
        $this->category_id = is_array($this->category_id)
            ? $this->category_id
            : [$this->category_id];
    }
}
