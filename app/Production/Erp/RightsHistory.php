<?php

namespace App\Production\Erp;

use App\Models\Erp\PermissionEvent;
use Illuminate\Support\Facades\Auth;

/** История прав ERP: выдача, отзыв, назначение роли, перевод прав роли в личные, изменение настроек */
class RightsHistory
{
    public function record(string $action, ?int $userId, ?int $permissionId = null, ?array $before = null, ?array $after = null, ?int $roleId = null): void
    {
        try {
            PermissionEvent::create([
                'user_id' => $userId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'action' => $action,
                'before' => $before,
                'after' => $after,
                'by_admin_id' => Auth::guard('admin')->id(),
            ]);
        } catch (\Throwable $error) {
            report($error);
        }
    }
}
