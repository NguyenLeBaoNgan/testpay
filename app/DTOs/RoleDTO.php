<?php

namespace App\DTOs;

class RoleDTO
{
    public function __construct(
        public int $userId,
        public string $roleName
    ) {}
}
