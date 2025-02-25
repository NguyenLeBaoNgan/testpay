<?php

namespace App\DTOs;


use Spatie\LaravelData\Data;

class FeedbackDTO extends Data
{
    public function __construct(
        // public ?string $id,
        public string $product_id,
        public string $user_id,
        public string $comment,
        public int $rating,
    ) {}
}
