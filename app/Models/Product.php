<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'description', 'price', 'quantity', 'category_id', 'user_id' ,'image'];

    public function category()
    {
        // return $this->belongsTo(Category::class);
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
}
