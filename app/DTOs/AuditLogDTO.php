<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Carbon\Carbon;

class AuditLogDTO extends Data
{
    public function __construct(
        public string $id,
        public string $user_id,
        public string $user_name,
        public string $action,
        public string $result,
        public string $ip_address,
        public string $browser,
        public Carbon $created_at
    ) {}
}
