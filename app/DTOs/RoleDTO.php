<?php

namespace App\DTOs;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
class RoleDTO extends Data
{
    public function __construct(
        // #[Required]
        // public int $userId,

        #[Required]
        public string $name,

        #[ArrayType]
        public array $permissions = []
    ) {}
}
