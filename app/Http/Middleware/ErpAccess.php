<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Доступ к служебным ajax-маршрутам ERP. Раньше у них не было проверки входа вовсе (их защищал только CSRF-токен,
 * который выдаёт любая страница сайта).
 *
 *   erp.access                — только администратор ERP (вошёл в админку, guard `admin`);
 *   erp.access:staff,contracts — ещё и сотрудник сайта с правом на любой из разделов (`section-<раздел>:site`),
 *                               как при открытии раздела сайта (`/get_section`);
 *   erp.access:*              — любой вошедший: администратор или сотрудник сайта.
 */
class ErpAccess
{
    public function handle(Request $request, Closure $next, string ...$siteSections)
    {
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        $user = Auth::guard('site')->user();
        if (!$user) {
            return response()->json(['error' => 401, 'auth' => false], 401);
        }

        $allowed = in_array('*', $siteSections, true)
            || collect($siteSections)->contains(fn (string $section) => $user->can("section-{$section}:site"));

        return $allowed ? $next($request) : response()->json(['error' => 403, 'message' => 'Нет доступа'], 403);
    }
}
