<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        string $action,
        ?Model $model = null,
        ?string $details = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model' => $model ? class_basename($model) : null,
            'model_id' => $model?->getKey(),
            'details' => $details ? ['message' => $details] : null,
        ]);
    }
}