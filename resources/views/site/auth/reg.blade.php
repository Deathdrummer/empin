<div class="auth__logo mb30px">
	<div><img src="{{asset('assets/images/ampin.png')}}" class="w25rem" alt=""></div>
</div>

<div class="row">
	<div class="col">
		<p class="auth__greetengs">{{__('auth.greetengs')}}</p>
		<p class="auth__subgreetengs mb24px">{{__('auth.subgreetengs')}}</p>
	</div>
	{{-- <div class="col-auto">
		<x-localebar group="auth" />
	</div> --}}
</div>

		



<div id="regForm" class="auth__formblock">
	<div class="auth__row">
		<x-input type="text" name="name" value="" class="auth__field" group="auth" label="Ваше имя" />
	</div>
	
	<div class="auth__row">
		<x-input type="email" name="email" value="" class="auth__field" group="auth" label="E-mail" />
	</div>
	
	<div class="auth__row">
		<x-input type="password" name="password" value="" class="auth__field" group="auth" label="Пароль" />
	</div>
	
	<div class="auth__row">
		<x-input type="password" name="password_confirmation" value="" class="auth__field" group="auth" label="Повторить пароль" />
	</div>
	
	<div class="auth__row">
		<x-checkbox name="agreement" class="auth__field" label="{!!__('auth.agreement')!!}" group="normal" />
	</div>
	
	<input type="hidden" name="locale" value="{{App::currentLocale()}}">
	
	<div class="auth__row">
		<x-button class="pointer" id="regBtn" group="auth">{{__('auth.reg')}}</x-button>
	</div>
</div>


<div class="row justify-content-center mt30px">
	<div class="col-auto"><p class="auth__textlink auth__reg" gotoauthform="auth">{{__('auth.sign_in')}}</p></div>
</div>