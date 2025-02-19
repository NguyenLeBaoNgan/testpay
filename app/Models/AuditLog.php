<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
class AuditLog extends Model
{
    use HasFactory;
    use HasUlids;
    protected $fillable = [
        'user_id', 'action', 'result', 'ip_address', 'browser'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
