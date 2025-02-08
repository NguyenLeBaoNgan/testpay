<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Nullable;

class UserDTO extends Data
{
    public function __construct(
        #[Nullable]
        #[Required]
        public ?string $name = null,
        #[Nullable]
        #[Required]
        public ?string $email = null,
        #[Nullable]
        #[Required]
        public ?string $password = null,
        #[ArrayType]
        public array $roles = [],
        #[ArrayType]
        public array $permissions = [],

         public ?string $status= "active",
    ) {
    }
}
