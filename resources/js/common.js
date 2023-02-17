jQuery(function() {
	
	initBaseScripts();
	
	/* if ($(selector).find('[editor]:not(.activated)').length > 0) {
		initEditors();
	} */

	
	//--------------------------------------------- FIXes
	$(document).on('focus gesturestart', function(e) {
		e.preventDefault();
	});

	/* под вопросом
	document.addEventListener('gesturestart', function (e) {
		e.preventDefault();
	}); */
	
	
	$('body').on('focus blur input', 'input, textarea, select, [contenteditable]', function(e) {
		let isContentEditable = typeof $(this).attr('contenteditable') !== 'undefined',
			hasGroup = typeof $(this).attr('inpgroup') !== 'undefined';
		
		let eventType = e.type || e.origType,
			tag = e.currentTarget?.tagName?.toLowerCase(),
			type =  e.currentTarget?.type?.toLowerCase(),
			value = isContentEditable ? getContenteditable(this) : $(this).val(),
			group = hasGroup ? $(this).attr('inpgroup')+'-' : '',
			wrapperClass = isContentEditable ? 'contenteditable' : (findWrapByInputType.indexOf(type) !== -1 ? type : tag),
			wrapperSelector = $(this).closest('.'+wrapperClass),
			isCleared = $(wrapperSelector).hasClass('cleared');	
			
			if (group) $(wrapperSelector).addClass(group+wrapperClass);
		
		
		if (wrapperSelector) {
			if (['checkbox'].indexOf(wrapperClass) !== -1 && ['input'].indexOf(eventType) !== -1) {
				let noChecked = typeof $(this).attr('checked') !== 'undefined';
				
				if (!isCleared) if ($(wrapperSelector).find('[errorlabel]').length) $(wrapperSelector).find('[errorlabel]').empty();
				if (!isCleared) if ($(wrapperSelector).hasClass(group+wrapperClass+'_error')) $(wrapperSelector).removeClass(group+wrapperClass+'_error');
				if (!isCleared) $(wrapperSelector).addClass(group+wrapperClass+'_changed');
				
				if (noChecked) {
					if (!isCleared) $(wrapperSelector).removeClass(group+wrapperClass+'_checked');
				} else {
					if (!isCleared) $(wrapperSelector).addClass(group+wrapperClass+'_checked');
				}
				
				if ($(this)[0].hasAttribute('checked')) {
					$(this).removeAttrib('checked');
				} else {
					$(this).setAttrib('checked');
				}
			
			} else if (['radio'].indexOf(wrapperClass) !== -1 && ['input'].indexOf(eventType) !== -1) {
				let fieldset = $(this).attr('fieldset');
				
				$('body').find('input[fieldset="'+fieldset+'"]').not(this).removeAttrib('checked');
				$(this).setAttrib('checked');
				$('body').find('input[fieldset="'+fieldset+'"]').not(this).closest('.'+group+wrapperClass).removeClass(group+wrapperClass+'_checked').removeClass(group+wrapperClass+'_error');
				//$(wrapperSelector).addClass(group+wrapperClass+'_changed');
				
				if ($(wrapperSelector).find('[errorlabel]').length) $(wrapperSelector).find('[errorlabel]').empty();
				if ($(wrapperSelector).hasClass(group+wrapperClass+'_error')) $(wrapperSelector).removeClass(group+wrapperClass+'_error');
				
				if (!isCleared) $(wrapperSelector).addClass(group+wrapperClass+'_checked');
				
			} else if (['checkbox', 'radio'].indexOf(wrapperClass) === -1) {
				if (['focusin', 'focus'].indexOf(eventType) !== -1) {
					$(wrapperSelector).addClass(group+wrapperClass+'_focused');
					
				} else if (['focusout', 'blur'].indexOf(eventType) !== -1) {
					$(wrapperSelector).removeClass(group+wrapperClass+'_focused');
				
				} else if (['input'].indexOf(eventType) !== -1) {
					
					if ($(wrapperSelector).find('[errorlabel]').length) $(wrapperSelector).find('[errorlabel]').empty();
					if ($(wrapperSelector).hasClass(group+wrapperClass+'_error')) $(wrapperSelector).removeClass(group+wrapperClass+'_error');
					
					if (!isCleared) $(wrapperSelector).addClass(group+wrapperClass+'_changed');
					
					if (!isCleared) {
						if (value !== null && value.length) $(wrapperSelector).addClass(group+wrapperClass+'_noempty');
						else $(wrapperSelector).removeClass(group+wrapperClass+'_noempty');
					}
				}
			}
		}
	});
	
	
	
	
	
	// показать/скрыть пароль
	// - добавить <i class="fa fa-eye" showpassword></i> внутрь .field
	$('body').on(tapEvent, '[showpassword]', function() {
		let eye = this,
			block = $(this).closest('.input');
		
		if ($(block).find('input[type="password"]').length) {
			$(block).find('input[type="password"]').prop('type', 'text');
			$(eye).html('<i class="fa-solid fa-eye" title="Скрыть пароль"></i>');
		
		} else if ($(block).find('input[type="text"]').length) {
			$(block).find('input[type="text"]').prop('type', 'password');
			$(eye).html('<i class="fa-solid fa-eye-slash" title="Показать пароль"></i>');
		}
	});
	
	
	
	//--------------------------------------------- Submit формы только при наличии аттрибута action
	$('body').on('submit', 'form:not([action])', function(e) {
		e.preventDefault();
		return false;
	});


	//--------------------------------------------- Запретить autocomplete
	$('body').on('focus', 'input[type="text"]:not([noedit]), input[type="password"]:not([noedit]), input[type="email"]:not([noedit]), input[type="tel"]:not([noedit])', function() {
		$(this).removeAttrib('readonly');
	});

	if (/iPhone|iPad|iPod/i.test(navigator.userAgent) == false) {
		$('body').on('blur', 'input[type="text"]:not([noedit]), input[type="password"]:not([noedit]), input[type="email"]:not([noedit]), input[type="tel"]:not([noedit])', function() {
			$(this).setAttrib('readonly');
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------- Нативное копирование
	let selection = null;
	const os = getOS();
	
	$(document).on('copy', function(e) {
		selection = getSelectionStr();
		
		if (e.type == 'copy' && os == 'Windows' && selection) {
			$.notify('Скопировано!', {autoHideDelay: 2000});
		
		} else if (e.type == 'copy' && os == 'MacOS' && selection) {
			$.notify('Скопировано!', {autoHideDelay: 2000});
		}
	});
	
	
	
	
	
	
	
	
	//--------------------------------------------- Вверх страницы
	var scrTop;
	scrTop = $(window).scrollTop();
	if (scrTop > $(window).height() * 2) {
		$('[scrolltop]').addClass('visible');
	} else {
		$('[scrolltop]').removeClass('visible');
	}

	$(window).on('scroll', function() {
		scrTop = $(this).scrollTop();
		if (scrTop > $(window).height() * 2) {
			$('[scrolltop]').addClass('visible');
		} else {
			$('[scrolltop]').removeClass('visible hover');
		}
	});

	$('[scrolltop]').on('click', function(e) {
		e.preventDefault();
		$('html, body').animate({scrollTop: 0}, 800, 'easeInOutQuint');
	});

	$('[scrolltop]').on('tap click mouseenter', function() {
		$(this).addClass('hover');
	});

	$('[scrolltop]').on('mouseleave', function() {
		$(this).removeClass('hover');
	});
	
	
	
	//--------------------------------------------- Параллакс
	/* $('body').find('[parallax]').each(function() {
		var thisParallaxData = $(this).attr('parallax').split('|');
		$(this).parallax({
			imageSrc: thisParallaxData[0],
			speed: parseFloat(thisParallaxData[1])
		});
	}); */
	
	
	
	
	// табы
	$('body').on(tapEvent, '[ddrtabsitem]', function() {
		if ($(this).hasClass('ddrtabsnav__item_active')) return false;
		let tabId = $(this).attr('ddrtabsitem');
		$(this).addClass('ddrtabsnav__item_active');
		$(this).siblings('[ddrtabsitem]').not(this).removeClass('ddrtabsnav__item_active');
		
		$('[ddrtabscontentitem="'+tabId+'"]').closest('[ddrtabscontent]').find('[ddrtabscontentitem].ddrtabscontent__item_visible').removeClass('ddrtabscontent__item_visible');
		$('[ddrtabscontentitem="'+tabId+'"]').addClass('ddrtabscontent__item_visible');
		
	});
	
	
	
	
});





$(document).trigger('initBaseScripts', () => {
	initBaseScripts();
});







// -------------------------------------------------------------------- Инициализация базовых скриптов


function initBaseScripts() {
	// -------------------------------------------------------------------- Работа с инпутами
	/* $('body').find('input:not([scripted]), textarea:not([scripted]), select:not([scripted]), [contenteditable]:not([scripted])').each(function(k, item) {
		let isContentEditable = typeof $(item).attr('contenteditable') !== 'undefined',
			hasGroup = typeof $(item).attr('inpgroup') !== 'undefined',
			tag = item?.tagName?.toLowerCase(),
			type =  item?.type?.toLowerCase(),
			value = isContentEditable ? getContenteditable(item) : $(item).val(),
			isDisabled = item?.disabled || false,
			group = hasGroup ? $(item).attr('inpgroup')+'-' : '',
			wrapperClass = isContentEditable ? 'contenteditable' : (findWrapByInputType.indexOf(type) !== -1 ? type : tag),
			wrapperSelector = $(item).closest('.'+wrapperClass);
			
			if (group) $(wrapperSelector).addClass(group+wrapperClass);
			
		if (['checkbox', 'radio'].indexOf(wrapperClass) === -1) {
			if (value !== null && value.length) $(wrapperSelector).addClass(group+wrapperClass+'_noempty');
			else $(wrapperSelector).removeClass(group+wrapperClass+'_noempty');
		
		} else {
			let isChecked = typeof $(this).attr('checked') !== 'undefined';
			if (isChecked) $(wrapperSelector).addClass(group+wrapperClass+'_checked');
		}
		
		if (isDisabled) $(wrapperSelector).addClass(group+wrapperClass+'_disabled');
		
		$(this).setAttrib('scripted');
	}); */
	
	
	$('body').find('[phonemask]:not([multicode]):not([scripted])').each(function() {
		var phonemask = $(this).attr('phonemask') || '(^^^) ^^^-^^-^^',
			code = $(this).attr('code') || '+7',
			placeholder = $(this).attr('placeholder') || (code+' '+phonemask).replace(/[\^&]/g, '_');
		$(this).mask(code+' '+phonemask, {autoclear: false}).attr({'placeholder': placeholder, 'type': 'tel'});
		$(this).setAttrib('scripted');
	});

	$('body').find('[mask]:not([scripted])').each(function() {
		var mask = $(this).attr('mask'),
			placeholder;
		if (!mask) return true;
		placeholder = $(this).attr('placeholder') || mask.replace(/[\^&]/g, '_');
		$(this).mask(mask, {autoclear: false}).attr({'placeholder': placeholder});
		$(this).setAttrib('scripted');
	});
	
	
	$('body').find('[numberformat]:not([scripted])').each(function() {
		var nf = $(this).attr('numberformat');
		if (nf != '') {
			var nf = nf.split('|'),
				afterDot = nf[0] != undefined ? nf[0] : 2,
				dot =nf[1] != undefined ? nf[1] : '.',
				space = nf[2] != undefined ? nf[2] : ' ';
			$(this).number(true, afterDot, dot, space);
		} else {
			$(this).number(true, 2, '.', ' ');
		}
		
		$(this).setAttrib('scripted');
	});


	$('body').find('.phone:not([scripted]) ,[phone]:not([scripted])').each(function() {
		var code = $(this).attr('code');
		$(this).attr('href', 'tel:'+$(this).text().trim().replace(/-|\(|\)|\s/g, '').replace(/^8/, '+7'));
		$(this).setAttrib('scripted');
	});

	$('body').find('.whatsapp:not([scripted]), [whatsapp]:not([scripted])').each(function() {
		var thisMess = $(this).data('message'),
			thisNumber = $(this).text().trim().replace(/-|\(|\)|\s/g, '').replace(/^8/, '+7'),
			code = $(this).attr('code');
		$(this).attr('href', 'whatsapp://send?phone='+thisNumber+'&abid='+thisNumber+'&text='+thisMess);
		$(this).setAttrib('scripted');
	});

	$('body').find('.email:not([scripted]), [email]:not([scripted])').each(function() {
		$(this).attr('href', 'mailto:'+$(this).text().trim());
		$(this).setAttrib('scripted');
	});
}
