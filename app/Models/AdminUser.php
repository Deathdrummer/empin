<?php namespace App\Models;

use App\Mail\ResetPassword;
use App\Mail\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements MustVerifyEmail {
	use HasFactory, Notifiable, HasRoles;
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'admin_users';
	
	
	
	/**
     * Раздел аутентификации
	 *
     * @var string
     */
	protected $guard = 'admin';
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'name',
		'pseudoname',
		'email',
		'password',
		'locale',
		'is_main_admin',
		'verification_token',
		'email_verified_at',
		'_sort'
	];
	
	
	/**
     * Скрытые поля (нельзя менять)
	 *
     * @var array
     */
	protected $hidden = [
		'password',
		'remember_token',
	];
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setPasswordAttribute($password) {
		$this->attributes['password'] = Hash::make($password);
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	//public function getPasswordAttribute($password) {
	//	return 'dima';
	//	//$this->attributes['password'] = 'dima';
	//}
	
	
	
	
	/**
	 * Send the email verification notification.
	 *
	 * @return void
	 */
	public function sendEmailVerificationNotification() {
		$this->notify(new VerifyEmail('admin'));
	}
	
	
	/**
	 * Send the password reset notification.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function sendPasswordResetNotification($token) {
		$this->notify(new ResetPassword($token, 'admin'));
	}
	
	
	
	
	/**
	 * @return bool
	 */
	public function isMainAdmin(): bool {
		return $this->is_main_admin;
	}
	
}