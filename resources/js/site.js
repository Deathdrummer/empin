require('./bootstrap');
require('@plugins/notify.min');
require('@plugins/jquery.maskedinput');
require('@plugins/jquery.number.min');
require('@plugins/jquery.scrollstop.min');
require('@plugins/jquery.mousewheel');
require('@plugins/dropdown');
require('@plugins/ddrFormSubmit');
require('@plugins/ddrInputs');
//require('@plugins/ddrInputsChain');
require('@plugins/ddrWaitBlock');
require('@plugins/ddrPopup');
require('@plugins/ddrDatepicker');
require('@plugins/ddrCRUD');
require('@plugins/card');
require('@plugins/ddrScrollX');
require('@plugins/tooltip');
require('@plugins/ddrTable');
require('@plugins/blockTable');
require('@plugins/ddrContextMenu');
require('@plugins/jquery.ui');
require('@plugins/ddrCalc');
require('@plugins/ddrFiles');
require('@plugins/ddrSwiper');




$.notify.defaults({
	clickToHide: true,
	autoHide: true,
	autoHideDelay: 15000,
	arrowShow: true,
	arrowSize: 5,
	//position: '...',
	elementPosition: 'top right',
	globalPosition: 'bottom right',
	style: 'bootstrap',
	className: 'success',
	showAnimation: 'fadeIn',
	showDuration: 200,
	hideAnimation: 'fadeOut',
	hideDuration: 100,
	gap: 2
});


// Configure time between final scroll event and
// `scrollstop` event to 650ms (default is 250ms).
$.event.special.scrollstop.latency = 20;



const SWIPE_THRESHOLD = 15;

window.longPressStart = function(event) {
    // Игнорируем все, кроме касаний пальцем
    if (event.pointerType !== 'touch') return;
    
    const element = event.currentTarget;
    clearTimeout(element._pressTimer); // Сбрасываем старый таймер на всякий случай

    // --- НОВОЕ: Запоминаем начальные координаты ---
    element._pressStartX = event.clientX;
    element._pressStartY = event.clientY;
    // ---

    const onHoldData = element.dataset.onhold;
    const duration = parseInt(element.dataset.duration, 10) || 1000;

    if (!onHoldData) return;

    let actionName = onHoldData;
    let extraArgs = [];
    
    const separatorIndex = onHoldData.indexOf(':');

    if (separatorIndex !== -1) {
        actionName = onHoldData.substring(0, separatorIndex);
        extraArgs = onHoldData.substring(separatorIndex + 1).split(',').map(arg => arg.trim());
    }

    if (typeof $ !== 'undefined' && typeof $[actionName] === 'function') {
        element._pressTimer = setTimeout(() => {
            const finalArgs = [element, ...extraArgs];
            $[actionName](...finalArgs);
            
            // Очищаем координаты после выполнения, чтобы избежать ложных срабатываний
            delete element._pressStartX;
            delete element._pressStartY;

        }, duration);
    } else {
        console.error(`Ошибка: функция $.${actionName} не найдена.`);
    }
}

// --- НОВАЯ ФУНКЦИЯ ДЛЯ ОТСЛЕЖИВАНИЯ СВАЙПА ---
window.longPressMove = function(event) {
    const element = event.currentTarget;

    // Если начальные координаты не записаны (т.е. нажатие не началось), выходим
    if (typeof element._pressStartX === 'undefined') return;

    const deltaX = Math.abs(event.clientX - element._pressStartX);
    const deltaY = Math.abs(event.clientY - element._pressStartY);

    // Если палец сдвинулся больше порога, отменяем долгое нажатие
    if (deltaX > SWIPE_THRESHOLD || deltaY > SWIPE_THRESHOLD) {
        //console.log('Обнаружен свайп, долгое нажатие отменено.');
        longPressCancel(event);
    }
}
// ---
	
window.longPressCancel = function(event) {
    const element = event.currentTarget;
    clearTimeout(element._pressTimer);

    // --- НОВОЕ: Очищаем сохраненные координаты при отмене ---
    // Это важно, чтобы сбросить состояние для следующего нажатия
    delete element._pressStartX;
    delete element._pressStartY;
    // ---
}




$(function() {
	
	$('body').on('contextmenu', function(e) {
		e.preventDefault();
	});
	
	
});




