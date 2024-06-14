<p class="fz20px color-dark">Даты</p>

<hr class="hr-light mt1rem mb1rem">

<div class="table">
	<table>
		<thead>
			<tr>
				<td class="w30rem"><strong>Переменная</strong></td>
				<td class="w30rem"><strong>Вывод</strong></td>
				<td><strong>Подсказка</strong></td>
		</td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><pre>${date_start}</pre></td>
				<td>дд.мм.гг</td>
				<td><p class="fz14px">Сокращенная запись по-умолчанию</p></td>
			</tr>
			<tr>
				<td><pre>${date_start_fyear}</pre></td>
				<td>дд.мм.гггг</td>
				<td><p class="fz14px">С полным годом</p></td>
			</tr>
			<tr>
				<td><pre>${date_start_human}</pre></td>
				<td>«дд» месяц гггг</td>
				<td><p class="fz14px">Человекопонятная форма записи даты</p></td>
			</tr>
			<tr>
				<td><pre>${title::вот такой заголовок: %s}</pre></td>
				<td>вот такой заголовок: title</td>
				<td><p class="fz14px">Вместо %s выведется значение переменной, сама же строка будет выводиться всегда, вне зависимости от содержания переменной</p></td>
			</tr>
			<tr>
				<td><pre>${hoz_method||какой-то текст}</pre></td>
				<td>какой-то текст или ничего</td>
				<td><p class="fz14px">Текст выведется только если значение переменной ИСТИНА или оно непустое</p></td>
			</tr>
			<tr>
				<td><pre>${hoz_method||какой-то текст [переменная]}</pre></td>
				<td>какой-то текст [значение переменной]</td>
				<td><p class="fz14px">Внутри условного текста интерполируется переменная</p></td>
			</tr>
		</tbody>
	</table>
</div>