<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;

class CategoryDTO extends Data
{
    public function __construct(
        public string $name,
        // public ?string $description,
        public ?string $user_id,
    ) {
    }
}
