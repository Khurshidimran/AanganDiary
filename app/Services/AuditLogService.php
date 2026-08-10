<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(string $action, string $module, ?Model $auditable = null, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
