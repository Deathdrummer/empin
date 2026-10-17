<?php

namespace App\Http\Middleware;

use App\Services\Messenger\Presence;
use Closure;
use Illuminate\Http\Request;

/**
 * Любой запрос приложения — сотрудник «в сети» (время обновляется не чаще раза в минуту)
 */
class TouchPresence
{
    public function handle(Request $request, Closure $next)
    {
        Presence::touch($request->user()?->staff_id ? (int) $request->user()->staff_id : null);

        return $next($request);
    }
}
