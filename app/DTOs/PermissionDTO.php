<?php
namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;

class PermissionDTO extends Data
{
    public function __construct(
        // #[Required]
        // public int $permissionId,

        #[Required]
        public string $name,

    ) {}
}
