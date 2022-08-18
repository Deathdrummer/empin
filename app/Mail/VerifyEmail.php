<?php namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail as NotificationsVerifyEmail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends NotificationsVerifyEmail {
	
	protected $guard;
	public function __construct($guard = '') {
		$this->guard = $guard ? $guard.'.' : '';
	}
	
	/**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }
		
		return URL::temporarySignedRoute(
            $this->guard.'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 500)),
            [
                'id' => sha1($notifiable->getKey()),
                'hash' => sha1($notifiable->getEmailForVerification())
            ]
        );
    }
}