<?php namespace App\Models;

use App\Mail\ResetPassword;
use App\Mail\VerifyEmail;
use App\Models\Traits\Collectionable;
use App\Models\Traits\Dateable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail {
	use HasFactory, Notifiable, HasRoles, Collectionable, Dateable;
	
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
     * Динамически создаваемые поля в выборке
	 *
     * @var string
     */
	//protected $appends = ['email_cyrillic'];
	
	
	/**
     * Поля разрешенные для редактирования
	 *
     * @var array
     */
	protected $fillable = [
		'staff_id',
		'email',
		'password',
		'locale',
		'department_id',
		'verification_token',
		'email_verified_at',
		'contract_colums',
		'contract_deps',
		'settings',
	];
	
	
	/**
     * Атрибуты, которые должны быть типизированы. (Конвертация полей при добавлении и получении)
	 *
     * @var array
     */
	protected $casts = [
        'contract_colums' 	=> 'array',
        'contract_deps' 	=> 'array',
        'settings' 			=> 'array',
    ];
	
	
	/**
     * Скрытые поля (нельзя менять)
	 *
     * @var array
     */
	protected $hidden = [
		'password',
		'temporary_password',
		'remember_token',
	];
	
	
	
	
	//protected $appends = ['name', 'pseudoname'];
	
	
	
	
	/**
     * Получить телефон, связанный с пользователем.
     */
    public function department() {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }
	
	
	
	
	
	
	/**
     * Договоры аккаунта
     */
	public function contracts() {
		return $this->belongsToMany(
			Contract::class,
			'user_contract',
			'account_id',
			'contract_id',
			'id',
			'id')
			->as('pivot')
			->withPivot('viewed', 'pinned');
	}
			
	
	
	
	public function userinfo() {
		return $this->belongsTo(Staff::class, 'staff_id', 'id')->withDefault();;
	}
	
	
	
	public function getFullNameAttribute() {
		$userData = $this->userinfo;
        return "{$userData->sname} {$userData->fname} {$userData->mname}";
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
	public function getEmailAttribute($email = null) {
		return $email ? decodeEmail($email) : null;
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function getEmailVerifiedAtAttribute($value) {
		return $value ? Carbon::create($value)->timezone('Europe/Moscow') : null;
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	//public function setEmailAttribute($email) {
	//	$encoder = new IdnAddressEncoder();
	//	$this->attributes['email'] = $encoder->encodeString($email);
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
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function scopeFromStaff($query, $userId = false) {
		if (!$userId) return $query;
		return $query->where('staff_id', $userId);
	}
	
	
	
	
	
	
}