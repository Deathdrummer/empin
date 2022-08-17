import "./ddrPopup.css";


window.ddrPopup = function(settings = {}, callback = false) {
	let {
		title, // заголовок
		width, // ширина окна
		html, // контент
		buttons, // массив кнопок
		buttonsAlign, // выравнивание вправо
		disabledButtons, // при старте все кнопки кроме закрытия будут disabled
		closePos, // расположение кнопки "close" left - слева, right - справа
		closeByButton, // Закрывать окно только по кнопкам [ddrpopupclose]
		close, // заголовок кнопки "закрыть"
		winClass, // добавить класс к модальному окну
		contentToCenter, // весь контент по центру вертикально и горизонтально
		topClose // верхний крестик "закрыть"
	} = _.assign(settings, {
		title: false,
		width: false,
		html: '',
		buttons: false,
		buttonsAlign: 'right',
		disabledButtons: false,
		closePos: 'right',
		closeByButton: false,
		close: false,
		winClass: false,
		contentToCenter: false,
		topClose: true
	}),
	animationTime = 0.2,
	popupCloseTOut,
	buttonsHtml = '',
	topCloseHtml = '',
	titleHtml = '',
	popupHtml = '',
	ddrPopupId = 'ddrpopup'+generateCode('LlnlL'),
	ddrPopupSelector = '#'+ddrPopupId;
		
	// ddrCssVar
		
		
	html +=	'<div class="ddrpopup" id="'+ddrPopupSelector+'" ddrpopup>';
	html +=		'<div class="ddrpopup__wrap">';
	html +=			'<div class="ddrpopup__container">';
	html +=				'<div class="ddrpopup__win noselect" ddrpopupwin>';
	html +=					'<div class="ddrpopup__wait">';
	html +=						'<div class="ddrpopupwait" ddrpopupwaitblock>';
	html +=							'<img src="/assets/images/loading.gif" ddrwaiticon class="ddrpopupwait__icon">';
	html += 						'<p class="ddrpopupwait__label" ddrpopupwait></p>';
	html +=						'</div>';
	html += 				'</div>';
/*	
	html += 				'<div class="ddrpopup__header">';
	html += 					'<div class="ddrpopup__title ddrpopup__title_h1" ddrpopuptitle>title</div>';
	html += 					'<div class="ddrpopup__close" title="Закрыть" ddrpopupclose></div>';
	html += 				'</div>';
	
	html += 				'<div class="ddrpopup__content d-flex align-items-center justify-content-center">';
	html += 					'<div ddrpopupcontent>html</div>';
	html += 				'</div>';
	
	html += 				'<div class="ddrpopup__footer">';
	html += 					'<div class="ddrpopup__buttons" ddrpopupbuttons>buttonsHtml</div>';
	html += 				'</div>';*/
	html += 			'</div>';
	html += 		'</div>';
	html += 	'</div>';
	html +=	'</div>';
	
	_insertHtml(html);
	
	
	
	/*axios.get('/ajax/popup', {
		responseType: 'text'
	}).then(function ({data, status, statusText, headers, config}) {
		console.log({data, status, statusText, headers, config});
		if (status == 200) _insertHtml(data);
		//if (data.logout) pageReload();	
	}).catch(function(data) {
		console.log(data);
	});*/
	//alert('ddrPopup');
}


function _insertHtml(html = null) {
	if (!html) throw new Error('ddrPopup -> _insertHtml ошибка - не переданы данные!');
	if ($('body').find('[ddrpopup]').length) {
		$('body').find('[ddrpopup]').replaceWith(html);
	} else {
		$('body').append(html);
	}
	
}