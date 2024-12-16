<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PersonalAccessToken extends Model
{
    use HasFactory, HasUlids;
    protected $fillable = ['tokenable_id', 'tokenable_type', 'name', 'token', 'abilities', 'expires_at'];

}