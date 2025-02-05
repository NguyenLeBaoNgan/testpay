<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Order extends Model
{
    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'total_amount', 'status'];


    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    protected static function booted()
    {
        static::deleting(function ($order) {
            $order->items()->delete();
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
