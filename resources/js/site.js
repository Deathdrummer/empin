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
require('@plugins/tooltip');
require('@plugins/ddrTable');
require('@plugins/blockTable');
require('@plugins/ddrContextMenu');
require('@plugins/jquery.ui');




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
$.event.special.scrollstop.latency = 20;


$(function() {
	
	$('body').on('contextmenu', function(e) {
		e.preventDefault();
	});
	
});