<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Payment extends Model
{
    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    protected $fillable = ['order_id', 'method',  'payments_status', 'payment_amount', 'transaction_id'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function paymentDetails()
    {
        return $this->hasOne(PaymentDetails::class);
    }
}
