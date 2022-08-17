<h2>Вы зарегистрироны в системе.</h2>

Вам Выдан доступ:
<p>Email: {{$user['email']}}</p>

@isset($user['password'])
<p>Ваш пароль: {{$user['password']}}</p>
@else
<p>Пароль необходимо будет восстановить с помощью ссылки <strong>"забыли пароль?"</strong></p>
@endisset