<?php namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Auth\EmailVerificationRequest;


class AdminEmailVerificationRequest extends EmailVerificationRequest {
	
	protected $guard;
	
	
	/**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }
	
	
	public function __construct() {
		$this->guard = 'admin';
	}
}