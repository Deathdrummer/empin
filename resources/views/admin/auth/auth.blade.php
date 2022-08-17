<div class="auth__logo mb30px">
	<div><img src="{{asset('assets/images/ampin.png')}}" class="w25rem" alt=""></div>
	{{-- <p>{{$company_name ?? 'no'}}</p> --}}
</div>


<div class="row mb24px">
	<div class="col">
		<p class="auth__greetengs">{{__('auth.greetengs')}}</p>
		<p class="auth__subgreetengs">{{__('auth.subgreetengs')}}</p>
	</div>
	<div class="col-auto">
		<x-localebar group="auth" />
	</div>
</div>

<div id="authForm" class="auth__formblock">
	<div class="auth__row">
		<x-input type="email" name="email" value="deathdrumer@yandex.ru" class="auth__field" group="auth" label="E-mail" />
	</div>
	
	<div class="auth__row">
		<x-input type="password" name="password" value="DeatH123654" class="auth__field" group="auth" label="Пароль" />
	</div>
	
	<input type="hidden" id="localeInp" name="locale" value="{{App::currentLocale()}}">
	
	<div class="auth__row">
		<x-button class="pointer" id="authBtn" group="auth">{{__('auth.sign_in')}}</x-button>
	</div>
</div>


<div class="row justify-content-between mt30px">
	<div class="col-auto"><p class="auth__textlink auth__forgot" gotoauthform="forgot-password">{{__('auth.forgot_pass')}}</p></div>
	
	@unless($hasMainAdmin)
		<div class="col-auto"><p class="auth__textlink auth__reg" gotoauthform="reg">{{__('auth.reg')}}</p></div>
	@endunless
</div>