<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Может ли учётная запись работать в приложении */
class AccountAccess
{
    public function isActive(?User $user): bool
    {
        if (!$user || $user->getAuthPassword() === null || $user->getAuthPassword() === '') {
            return false;
        }

        return (bool) Staff::whereKey($user->staff_id)->value('working');
    }

    public function isStaffActive(Staff $staff): bool
    {
        return (bool) $staff->working;
    }

    /**
     * Сотрудники, которые сейчас могут работать в приложении (те же условия, что в isActive): «работает» в ERP и
     * учётная запись с паролем
     */
    public function activeStaffQuery(): Builder
    {
        return DB::table('staff as s')
            ->where('s.working', 1)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('users as u')
                ->whereColumn('u.staff_id', 's.id')
                ->whereNotNull('u.password')
                ->where('u.password', '<>', ''));
    }

    public function isStaffIdActive(int $staffId): bool
    {
        return $this->activeStaffQuery()->where('s.id', $staffId)->exists();
    }
}
