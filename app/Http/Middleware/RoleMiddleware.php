<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role, $permission = null) {
		
		/* 
		if (!auth('admin')->user()->hasRole($role) || ($permission !== null && !auth('admin')->user()->can($permission))) {
            $request->merge([
				'section' => 'denied',
			]);
        }
		 */
		
		
        /* if(!auth('admin')->user()->hasRole($role)) {
            return response()->json(['status' => 401, 'message' => __('errors.401')]);
        }
        if($permission !== null && !auth('admin')->user()->can($permission)) {
            return response()->json(['status' => 401, 'message' => __('errors.401')]);
        } */
		return $next($request);
    }
}