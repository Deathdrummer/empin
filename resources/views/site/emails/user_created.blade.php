@isset($user['password'])
	<h2>Вы зарегистрированы в системе!</h2>
	<p>Email: {{$user['email']}}</p>
	<p>Ваш пароль: {{$user['password']}}</p>
	<p><a href="{{url('/')}}">Войти в систему</a></p>
@else
	<h2>Воостановление доступа</h2>
	<h4>Аккаунт: <strong>{{$user['email']}}</strong></h4>
	<p>Пароль можно восстановить <a href="{{url('/')}}"><strong>здесь</strong></a> кликнув на ссылку: <strong>"забыли пароль?"</strong></p>
@endisset