<?php

namespace App\DTOs;

use App\Models\User;

class UserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public array $roles,
        public array $permissions
    ) {}
}
