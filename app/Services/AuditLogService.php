<?php

namespace App\Services;

use App\DTOs\AuditLogDTO;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;

class AuditLogService
{
    public function getAllLogs(): array
    {
        return AuditLog::with('user')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($log) => new AuditLogDTO(
                id: $log->id,
                user_id: $log->user_id,
                user_name: $log->user->name ?? 'Unknown',
                action: $log->action,
                result: $log->result,
                ip_address: $log->ip_address,
                browser: $log->browser,
                created_at: $log->created_at
            ))
            ->toArray();
    }
}
