<?php namespace App\Models;

use App\Mail\ResetPassword;
use App\Mail\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail {
	use HasFactory, Notifiable, HasRoles;
	
	/**
     * Таблица
	 *
     * @var string
     */
	protected $table = 'users';
	
	
	/**
     * Раздел аутентификации
	 *
     * @var string
     */
	protected $guard = 'site';
	
	
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
		'department_id',
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
     * Получить телефон, связанный с пользователем.
     */
    public function department() {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }
	
	
	
	
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
		$this->notify(new VerifyEmail('site'));
	}
	
	
	/**
	 * Send the password reset notification.
	 *
	 * @param  string  $token
	 * @return void
	 */
	public function sendPasswordResetNotification($token) {
		$this->notify(new ResetPassword($token, 'site'));
	}
	
	
	
	
}