<h2>Вы зарегистрироны в системе.</h2>

Вам Выдан доступ:
<p>Email: {{$adminUser['email']}}</p>

@isset($adminUser['password'])
<p>Ваш пароль: {{$adminUser['password']}}</p>
@else
<p>Пароль необходимо будет восстановить с помощью ссылки <strong>"забыли пароль?"</strong></p>
@endisset