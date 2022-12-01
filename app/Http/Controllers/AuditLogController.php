<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {
    }

    public function paginate(Request $request)
    {
        return $this->auditLogService->paginate($request);
    }

    public function getModels(Request $request)
    {
        return $this->auditLogService->getModels();
    }
}
