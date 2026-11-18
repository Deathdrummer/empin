<?php

namespace App\Production\Erp;

use App\Models\AuditEntry;
use App\Models\Staff;
use App\Production\Objects\ObjectDirectory;
use Illuminate\Support\Facades\Auth;

/** Журнал изменений справочников производства в ERP: кто из администраторов ERP, что и когда */
class ErpAudit
{
    public static function write(string $entityType, int $entityId, string $action, ?array $before, ?array $after): void
    {
        $admin = Auth::guard('admin')->user();
        AuditEntry::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'actor_staff_id' => null,
            'before' => $before,
            'after' => $after,
            'context' => ['admin' => $admin ? ['id' => $admin->id, 'name' => $admin->name ?: $admin->pseudoname] : null],
        ]);
    }

    /** Сотрудник для журнала: id и ФИО на момент изменения */
    public static function person(?int $staffId): ?array
    {
        if (!$staffId) {
            return null;
        }
        $staff = Staff::find($staffId, ['id', 'sname', 'fname', 'mname']);

        return ['id' => $staffId, 'name' => $staff ? ObjectDirectory::name($staff->sname, $staff->fname, $staff->mname) : null];
    }

    /** Журнал записи для окна «История»: новые сверху */
    public static function history(string $entityType, int $entityId): array
    {
        return AuditEntry::where('entity_type', $entityType)->where('entity_id', $entityId)->orderByDesc('id')->get()
            ->map(fn (AuditEntry $e) => [
                'at' => $e->created_at,
                'action' => $e->action,
                'before' => $e->before,
                'after' => $e->after,
                'admin' => $e->context['admin']['name'] ?? null,
            ])->all();
    }
}
