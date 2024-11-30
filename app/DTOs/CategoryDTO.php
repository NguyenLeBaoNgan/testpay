<?php
namespace App\DTOs;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class CategoryDTO extends Data
{
    public function __construct(

        public string $name,
        // public ?string $description,
        public ?string $user_id ,
    ) {}
}
