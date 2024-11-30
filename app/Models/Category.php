<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Category extends Model
{
    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'user_id'];
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
