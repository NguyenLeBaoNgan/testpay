<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Transaction extends Model
{

    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    protected $table = 'transactions';
    protected $fillable = [
        'gateway',
        'transaction_date',
        'account_number',
        'sub_account',
        'amount_in',
        'amount_out',
        'accumulated',
        'code',
        'transaction_content',
        'reference_number',
        'body',
    ];
    protected $dates = ['transaction_date', 'created_at', 'updated_at'];
    public $timestamps = true;
    public function setTransactionDateAttribute($value)
    {
        $this->attributes['transaction_date'] = \Carbon\Carbon::parse($value);
    }
}
