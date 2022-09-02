@isset($adminUser['password'])
	<h2>Вы зарегистрироны в системе.</h2>
	<p>Email: {{$adminUser['email']}}</p>
	<p>Ваш пароль: {{$adminUser['password']}}</p>
	<p><a href="{{url('/admin')}}">Войти в систему</a></p>
@else
	<h2>Воостановление доступа</h2>
	<h4>Аккаунт: <strong>{{$adminUser['email']}}</strong></h4>
	<p>Пароль можно восстановить <a href="{{url('/admin')}}"><strong>здесь</strong></a> кликнув на ссылку: <strong>"забыли пароль?"</strong></p>
@endisset