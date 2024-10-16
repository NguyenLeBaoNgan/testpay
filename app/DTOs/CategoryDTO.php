<?php
namespace App\DTOs;

class CategoryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $user_id
    ) {}
}
