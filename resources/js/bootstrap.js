window.$ = window.jQuery = require('jquery');
window._ = require('lodash');
require('tapjs');


window.strtr = require('locutus/php/strings/strtr');

 
_.mixin({
	'takeFromObject': function(obj, item) {
		let removedItem = obj[item];
		delete obj[item];
		return removedItem;
	}
});


/* Глобальные переменные */
window.findWrapByInputType = ['file', 'checkbox', 'toggle', 'radio', 'contenteditable']; // plugins/ddrInputs: искать обрамляющие селекторы по типу, а не по тегу


jQuery.expr[":"].icontains = jQuery.expr.createPseudo(function(arg) {
    return function(elem) {
        return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
    };
});

jQuery.fn.tagName = function() {
    return this?.prop("tagName")?.toLowerCase();
};



window.getImageUrl = function(path = null) {
	if (!path) console.error('getImageUrl ошибка! Не передан URL');
	return new URL(path, location.origin).href;
}



/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');
window.axios.defaults.baseURL = process.env.APP_URL;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axiosQuery = require('@plugins/axiosQuery').default;

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });





require('@/functions');
require('@/common');
require('@/sections');