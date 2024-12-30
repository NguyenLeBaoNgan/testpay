<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Str;

class CategoryProduct extends Model
{
    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    protected $table = 'category_product';
    protected $fillable = ['category_id', 'product_id'];

    protected static function booted()
    {
        static::creating(function ($categoryProduct) {
            if (!$categoryProduct->id) {
                $categoryProduct->id = Str::ulid();
            }
        });
    }
}
