<?php namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;


class UserEmailVerificationRequest extends EmailVerificationRequest {
	
	protected $guard;
	
	public function __construct() {
		$this->guard = 'site';
	}
}