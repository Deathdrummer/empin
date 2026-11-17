<?php namespace App\Providers;

use App\Models\User;
use App\Services\AccountAccess;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();
		
		Gate::before(function ($user) {
			if (method_exists($user, 'isMainAdmin') && $user->isMainAdmin()) {
				return true;
			}
		});

		// Токен приложения не действует, если ERP отключила сотрудника или стёрла пароль учётки (П-ПР-24)
		Sanctum::authenticateAccessTokensUsing(
			fn ($accessToken, bool $isValid) => $isValid && app(AccountAccess::class)->isActive($accessToken->tokenable instanceof User ? $accessToken->tokenable : null)
		);

    }
}
