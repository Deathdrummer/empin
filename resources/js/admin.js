require('./bootstrap');
require('@plugins/notify.min');
require('@plugins/jquery.maskedinput');
require('@plugins/jquery.number.min');
require('@plugins/jquery.scrollstop.min');
require('@plugins/jquery.mousewheel');
require('@plugins/dropdown');
require('@plugins/ddrFormSubmit');
require('@plugins/ddrInputs');
require('@plugins/ddrWaitBlock');
require('@plugins/ddrPopup');
require('@plugins/ddrDatepicker');
require('@plugins/ddrCRUD');
require('@plugins/card');
require('@plugins/ddrScrollX');
require('@plugins/ddrFloatingBlock');




$.notify.defaults({
	clickToHide: true,
	autoHide: true,
	autoHideDelay: 15000,
	arrowShow: true,
	arrowSize: 5,
	//position: '...',
	globalPosition: 'top right',
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
$.event.special.scrollstop.latency = 650;


$(function() {
	
	let changeInputTOut, prevSetting;
		//settingController = new AbortController();
	$.setSetting = function(item = false, setting = false, saveTOut = 0, callback = false) {
		if (item === false || setting === false) {
			throw new Error('setSetting не переданы все аргументы!');
			return false;
		} 
		
		let s = typeof item === 'object' ? setting : item;
		if (prevSetting == s) {
			clearTimeout(changeInputTOut);
		} else {
			prevSetting = s;
		}
		
		if (typeof saveTOut == 'function') callback = saveTOut;
		
		//settingController.abort();
		changeInputTOut = setTimeout(() => {
			let value;
			if (typeof item === 'object') { // если передан селектор
				let tag = item?.tagName?.toLowerCase(),
					type = typeof $(item).attr('contenteditable') !== 'undefined' ? 'contenteditable' : item?.type?.toLowerCase()?.replace('select-one', 'select'),
					group = typeof $(item).attr('inpgroup') !== 'undefined' ? $(item).attr('inpgroup')+'-' : '',
					wrapperClass = findWrapByInputType.indexOf(type) !== -1 ? group+type : group+tag,
					wrapperSelector = $(item).closest('.'+wrapperClass).length ? $(item).closest('.'+wrapperClass) : false;
				
				if (type == 'checkbox') {
					value = $(item).is(':checked') ? 1 : 0;
				} else {
					value = $(item).val() || null;
				}
			
			} else {
				value = setting || null;
				setting = item;
			}
			
			
			let group = _.replace(location.pathname, /\/?admin\/?/, '') || 'common';
			
			axiosQuery('put', 'api/settings', {
				key: setting,
				value,
				group
			}, 'json').then(({data, error, status, headers}) => {
				
				if (error) {
					console.log(error);
					$.notify(error?.message, 'error');
					
					if (error.errors) {
						$(item).ddrInputs('error');
					}
				}
				
			}).catch(err => {
				if (axios.isCancel(err)) {
					console.log('Request canceled');
				} else {
					$.notify('Ошибка сохранения данных', 'error');
				}
			});
			
		}, saveTOut);
	}
	
	
	
	
});