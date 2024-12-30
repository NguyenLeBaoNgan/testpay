<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class PermissionDTO extends Data
{
    public function __construct(
        // #[Required]
        // public int $permissionId,

        #[Required]
        public string $name,
    ) {
    }
}
