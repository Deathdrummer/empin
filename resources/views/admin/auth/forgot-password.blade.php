<div class="auth__logo mb30px">
	<div><img src="{{asset('assets/images/ampin.png')}}" class="w25rem" alt=""></div>
	{{-- <p>{{$company_name ?? ''}}</p> --}}
</div>


<div class="row mb24px">
	<div class="col">
		<p class="auth__subgreetengs">{{__('auth.enter_the_password')}}</p>
	</div>
</div>

<div id="authForm" class="auth__formblock">
	<div class="auth__row" id="forgotPasswordForm">
		<x-input type="email" name="email" value="{{$email}}" placeholder="Email" class="auth__field" group="auth" label="E-mail" />
	</div>
	
	<div class="auth__row">
		<x-button class="pointer" group="auth" id="forgotPasswordBtn">{{__('auth.submit')}}</x-button>
	</div>
</div>


<div class="row justify-content-between mt30px">
	<div class="col-auto">
		<p class="auth__textlink auth__forgot" gotoauthform="auth"><i class="fa-solid fa-angle-left"></i> {{__('auth.go_back')}}</p>
	</div>
	
</div>