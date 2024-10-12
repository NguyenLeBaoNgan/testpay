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

    public static function fromModel(User $user): self
    {
        return new self(
            $user->id,
            $user->name,
            $user->email,
            $user->getRoleNames()->toArray(),
            $user->getAllPermissions()->pluck('name')->toArray() // Lấy danh sách các permissions
        );
    }
}
