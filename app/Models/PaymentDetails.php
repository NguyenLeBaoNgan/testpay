<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PaymentDetails extends Model
{
    use HasUlids;
    use HasFactory;
    protected $primaryKey = 'id';
    protected $fillable = [
        'payment_id',
        'address',
        'phone',
        'email',
        'note',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
