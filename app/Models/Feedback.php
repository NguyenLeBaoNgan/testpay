<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    use HasFactory, HasUlids;
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'product_id',
        'user_id',
        'comment',
        'rating',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
