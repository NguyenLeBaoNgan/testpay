<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Account extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'user_id',
        'account_type',
        'account_name',
        'account_number',
        'bank_name',
        'e_wallet_provider',
        'gateway',
        'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
