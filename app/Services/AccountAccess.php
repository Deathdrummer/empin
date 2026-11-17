<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;

/**
 * Может ли учётная запись работать в приложении. ERP отключает человека так:
 *  - раздел «Сотрудники»: «Работает» выключено (`staff.working = 0`) или удаление (ещё и пароль стирается);
 *  - раздел «Персонал»: удаление — пароль стирается.
 * Отключение действует сразу, в том числе на уже выданные токены (ТЗ, П-ПР-24). Данные не удаляются (п. 10.3).
 */
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
}
