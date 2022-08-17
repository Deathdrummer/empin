<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
		<meta charset="UTF-8" />
		<meta name="author" content="Дмитрий Сайтотворец" />
		<meta name="copyright" content="ShopDevelop &copy; Web разработка" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<meta name="format-detection" content="telephone=no"> {{-- отключение автоопределения номеров для Safari (iPhone / IPod / IPad) и Android браузера --}}
		<meta http-equiv="x-rim-auto-match" content="none"> {{-- отключение автоопределения номеров для BlackBerry --}}
		<meta name="csrf-token" content="{{csrf_token()}}"> {{-- CSRF Token --}}
		
		{{-- не кэшировать, если это не продакшн версия --}}
		@unless(config('app.env') == 'production')
			<meta http-equiv="cache-control" content="no-cache">
			<meta http-equiv="expires" content="1">
		@endunless
	
		
		<link rel="stylesheet" href="{{mix('assets/css/app.css')}}">
		<link rel="stylesheet" href="{{mix('assets/css/admin.css')}}">
		
		<script defer src="{{mix('assets/js/admin.js')}}"></script>
		{{-- <script src="{{mix('assets/js/manifest.js')}}"></script> --}}
		{{--<script src="{{mix('assets/js/vendor.js')}}"></script>--}}
		
		{{-- <script defer src="{{mix('assets/js/app.js')}}"></script> --}}
	
		<link rel="shortcut icon" href="{{asset('assets/images/shopdevelop_logo.png')}}" />
		
		<title>ЭМПИН</title>
	</head>
    
	<body>
		{{-- <p>{{$company_name}}</p> --}}
		@auth('admin')
			@include('admin.layout.admin')
		@else
			@include('admin.layout.auth')
		@endauth
		
		@stack('auth')
		@stack('scripts')
    </body>
</html>





<script type="module">
	let notifyAuth =  '{{$adminLogin ?? false}}' || null,
		notifyReg = '{{$adminRegister ?? false}}' || null,
		notifyResetPswd = '{{$adminResetPassword ?? false}}' || null,
		notifyEmailVerified = '{{$adminEmailVerified ?? false}}' || null;
	
	
	if (notifyAuth) {
		$.notify(notifyAuth);
	}
	
	
	if (notifyReg) {
		$.notify(notifyReg);
		ddrPopup({
			lhtml: 'auth.reg_success_text',
			centerMode: true,
			winClass: 'ddrpopup_dialog color-green'
		}).then(({state, close}) => {
			setTimeout(() =>{
				if (!state.isClosed) close();
			}, 5000);
		}).catch(err => {
			console.log(err);
		});
	}
	
	if (notifyResetPswd) {
		$.notify(notifyResetPswd);
	}
	
	if (notifyEmailVerified) {
		$.notify(notifyEmailVerified);
	}
	
	
</script>