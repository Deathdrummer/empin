$.fn.card = function(comand, ...params) {
	let destroyWait;
	const selector = this,
		comands = {
			ready() {
				$(selector).find('[cardwait]').addClass('card__wait_closing');
				setTimeout(() => {
					$(selector).find('[cardwait]').remove();
				}, 500);
			},
			wait(stat = true) {
				if (stat) {
					ddrWaitObj = $(selector).ddrWait({
						iconHeight: '34px',
						bgColor: '#ffffffd1'
					});
					destroyWait = ddrWaitObj?.destroy;
				}	
			},
			clear() {
				$(selector).children().not('[cardwait]').remove();
			},
			setData(data) {
				comands.clear();
				$(selector).prepend(data);
			},
			disableButton() {
				$(selector).find('[cardbutton]').ddrInputs('disable');
			},
			enableButton() {
				$(selector).find('[cardbutton]').ddrInputs('enable');
			},
			setWidth(width = false, cb = false, duration = 0) {
				if (width) {
				    $(selector).setAttrib('initw', _getElementWidth(selector)); // Сохраняем начальную ширину
				    //$(selector).addClass('card_center');
				    $(selector).animate({width: width}, duration, () => {
				    	if (cb) cb();
				    });
				    
				} else {
				    $(selector).animate({width: $(selector).attr('initw') || '100%'}, duration, function () {
				        $(selector).removeAttr('initw'); // Удаляем сохраненную ширину после анимации
				        //$(selector).removeClass('card_center');
				        if (cb) cb();
				    });
				}
			}
		};
	
	
	
	if (!comands[comand]) {
		console.error('card -> Такого метода не существует!');
		return false;
	}
	
	comands[comand](...params);
	
	return {
		destroyWait
	}
}







//-------------------------------------------------------------------------------------------------------


function _getElementWidth(selector) {
    let el = $(selector)[0]; // Получаем DOM-элемент
    if (!el) return null; // Если элемента нет, возвращаем null

    let computedWidth = window.getComputedStyle(el).getPropertyValue('width');

    // Проверяем, установлена ли ширина явно в CSS
    if (el.style.width === '') {
        return null; // Если ширина не указана явно, возвращаем null
    }
    
    return computedWidth; // Возвращаем ширину, если она есть
}
