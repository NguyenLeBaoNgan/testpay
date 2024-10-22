<?php

namespace App\DTOs;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
class UserDTO extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required]
        public string $email,

        #[Required]
        public string $password,

        #[ArrayType]
        public array $roles=[],

        #[ArrayType]
        public array $permissions=[]
    ) {}
}
