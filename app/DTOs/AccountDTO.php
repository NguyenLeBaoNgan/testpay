<?php
namespace App\DTOs;

use Spatie\LaravelData\Data;

class AccountDTO extends Data
{
    public function __construct(
        public string $account_type,
        public string $account_name,
        public string $account_number,
        // public ?string $bank_name = null,
        public ?string $e_wallet_provider = null,
        public bool $is_default = false
    ) {}
}
