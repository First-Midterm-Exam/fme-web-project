<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        $user = auth()->user();

        return AuditLog::create([
            'user_id' => $user instanceof User ? $user->id : null,
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'entity' => AuditLog::describe($model),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }
}
