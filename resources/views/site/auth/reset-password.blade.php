<div class="auth__logo mb30px">
	<div><img src="{{asset('assets/images/ampin.png')}}" class="w25rem" alt=""></div>
</div>


<div class="row mb24px">
	<div class="col">
		<p class="auth__subgreetengs">Введите новый пароль</p>
	</div>
</div>



<div id="resetPasswordForm" class="auth__formblock">
	<input type="hidden" name="token" value="{{$token}}">
	<input type="hidden" name="email" value="{{$email}}">
	
	<div class="auth__row" id="forgotPasswordForm">
		<x-input type="password" name="password" placeholder="Новый пароль" class="auth__field" group="auth" label="Новый пароль" />
	</div>
	
	<div class="auth__row" id="forgotPasswordForm">
		<x-input type="password" name="password_confirmation" placeholder="Новый пароль еще раз" class="auth__field" group="auth" label="Повторить новый пароль" />
	</div>
	
	<div class="auth__row">
		<x-button class="pointer" id="resetPasswordBtn" group="auth">Сбросить пароль</x-button>
	</div>
</div>