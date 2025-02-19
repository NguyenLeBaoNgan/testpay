<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
class AuditLogController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function index(): JsonResponse
    {
        $logs = $this->auditLogService->getAllLogs();
        return response()->json($logs);
    }

    public function deleteAll()
    {
        try {
            AuditLog::truncate();
            return response()->json(['message' => 'Xóa tất cả audit logs thành công'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi khi xóa logs'], 500);
        }
    }
}
