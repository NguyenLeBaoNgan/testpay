<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Support\Facades\Log;

class Permission extends SpatiePermission
{
    use HasFactory;
    use HasUlids;
    protected $primaryKey = 'id';
    // public $incrementing = false;
    // protected $keyType = 'string';

    // protected $fillable = ['id', 'name', 'guard_name'];

    // protected static function booted()
    // {
    //     static::creating(function ($permission) {
    //         if (empty($permission->id)) {
    //             $permission->id = (string) Str::ulid();
    //             Log::info('Generated ULID for Permission:', ['id' => $permission->id]);
    //         }
    //     });
    // }
}
