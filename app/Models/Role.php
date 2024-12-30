<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;
    use HasUlids;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'name', 'guard_name'];


}
