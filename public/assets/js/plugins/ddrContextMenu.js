$('body').on('contextmenu', '[contextmenu]', function(e) {
	let d = $(this).attr('contextmenu').split(':'),
		func = d[0],
		args = d[1]?.split(',');
	
	if (!$[func]) {
		e.preventDefault();
		throw new Error('Ошибка! contextmenu -> Указанная функция не создана!');
	}
	$[func](...args);
});
