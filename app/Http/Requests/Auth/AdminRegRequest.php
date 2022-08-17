<?php namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AdminRegRequest extends FormRequest {
    
	protected $stopOnFirstFailure = false; // Указывает, должен ли валидатор останавливаться при первом сбое правила.
	
	/**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'name'		=> 'string|min:3|max:30',
			'email'		=> 'required|email|unique:admin_users',
			'password'	=> 'required|min:8|max:30|confirmed',
			'locale'	=> 'required|string',
			'agreement'	=> 'required|accepted',
        ];
    }
	
	
	
	
	
	/**
	* Get the error messages for the defined validation rules.
	*
	* @return array
	*/
	public function messages()
	{
		return [
			'email.unique' => 'Такой :attribute уже существует!',
			'email.email' => 'Некорректный :attribute!',
			'email.required' => __('custom.welcome'),
		];
	}
	
	
	/**
	* Get custom attributes for validator errors.
	*
	* @return array
	*/
	public function attributes()
	{
		return [
			'email' => 'адрес почты',
		];
	}
	
	
	/**
	 * Return Exception for AJAX request with status 200
	 * @param 
	 * @return 
	 */
	protected function failedValidation(Validator $validator) {
		return response()->json([
			'errors' 	=> $validator->errors(),
			'status'	=> 422
		]);
		/* throw new HttpResponseException(response()->json([
			'errors' 	=> $validator->errors(),
			'status'	=> 422
		], 422)); */
	}
	
}