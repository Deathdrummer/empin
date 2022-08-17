<?php namespace App\Mail;

use Illuminate\Auth\Notifications\ResetPassword as NotificationsResetPassword;

class ResetPassword extends NotificationsResetPassword {
	
	protected $guard;
	public $token;
	
	public function __construct($token, $guard = '') {
		$this->guard = $guard ? $guard.'.' : '';
		$this->token = $token;
	}
	
	/**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }
		
        return url(route($this->guard.'password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}