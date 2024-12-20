/*window.ddrCalc = (value, percent, reverse = false) => {
	value = Number(value);
	percent = Number(percent);
	
	if (reverse) return value * ((100 - percent) / 100);
	return value / ((100 - percent) / 100);
	
	if (reverse) return value / (1 + percent / 100);
	return value * (1 + percent / 100);
}




middleware: [(value, calc) => {
	return calc('nds', Number($(row).find('[calcprice^="price_gen|"]').text()), percentNds, true); // true - это если реверс
}, false (это если двухсторонняя привязка)],

*/

$.fn.ddrCalc = function(data = []) {
	if (!data) throw Error('ddrCalc ошибка! Такого метода не существует!');
	
	const mainSelector = this;
	
	
	let target = {
		items: []
	};
	let eventListeners = new Proxy(target, {});
	
	let eventRandStr = generateCode('LllnnnlLnLnLllnnn');

	const methods = new DdrCalc(mainSelector, eventListeners, eventRandStr); 
	
	data.forEach((item) => {
		
		const {method} = item;
		
		delete(item['method']);
		
		if (['percent', 'nds', 'percent_only','count_days'].indexOf(method) === -1) throw Error('ddrCalc ошибка! метода «'+method+'» не существует!');
		
		methods[method](item);
	});
	
	
	return {
		calc() {
			$(mainSelector).trigger('input.'+eventRandStr);
		},
		destroy() {
			eventListeners.items.forEach(function(evt) {
				$(evt.target).off('input.'+eventRandStr/*+' '+'paste.'+eventRandStr*/);
			});
		}
	};
};





class DdrCalc {
	
	mainSelector = null;
	eventListeners = null;
	inputEvent = null;
	
	constructor(mainSelector, eventListeners, eventRandStr) {
		this.mainSelector = mainSelector;
		this.eventListeners = eventListeners;
		this.inputEvent = 'input.'+eventRandStr/*+' '+'paste.'+eventRandStr*/;
	}
	
	
	
	percent(data) {
		let {
			selector, // куда вставлять данные. Можно вызвать функцию: (selector, value) => $(selector).setAttrib('replacer', value)
			percent, // процент для расчета
			reverse, // в обратном направлении
			twoWay, // двухсторонняя привязка
			middleware, // промежуточный расчет значения. Пример: middleware: [(value, calc) => calc('nds', value, percentNds), false],
			numberFormat, // фрматировать вставляемое значение. Эквивалент $.number: [2, '.', ' '],
			stat // производить расчет
		} = $.extend({
			selector: null,
			percent: 0,
			reverse: false,
			twoWay: false,
			middleware: false,
			numberFormat: false,
			stat: () => true
		}, data),
			thisCls = this;
		
			
		$(thisCls.mainSelector).on(thisCls.inputEvent, function(e) {
			if (!stat()) return;
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('percent', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}

			const calcValue = numberFormat ? $.number(_.round(result, 2), ...numberFormat) : _.round(result, 2);
			
			thisCls._insertValue(selector, calcValue);
			
			thisCls.eventListeners.items.push(e);
		});	
		
		if (twoWay) {
			$(selector).on(thisCls.inputEvent, function(e) {
				if (!stat()) return;
				let val = thisCls._valToNumber(e.target.value, 2);
				
				let result = _.round(thisCls._calc('percent', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				const calcValue = numberFormat ? $.number(_.round(result, 2), ...numberFormat) : _.round(result, 2);
				
				thisCls._insertValue(thisCls.mainSelector, calcValue);
				
				thisCls.eventListeners.items.push(e);
			});
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	percent_only(data) {
		let {
			selector, // куда вставлять данные. Можно вызвать функцию: (selector, value) => $(selector).setAttrib('replacer', value)
			percent, // процент для расчета
			reverse, // в обратном направлении
			twoWay, // двухсторонняя привязка
			middleware, // промежуточный расчет значения. Пример: middleware: [(value, calc) => calc('nds', value, percentNds), false],
			numberFormat, // фрматировать вставляемое значение. Эквивалент $.number: [2, '.', ' '],
			stat // производить расчет
		} = $.extend({
			selector: null,
			percent: 0,
			reverse: false,
			twoWay: false,
			middleware: false,
			numberFormat: false,
			stat: () => true
		}, data),
			thisCls = this;
		
			
		$(thisCls.mainSelector).on(thisCls.inputEvent, function(e) {
			if (!stat()) return;
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('percent_only', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}

			const calcValue = numberFormat ? $.number(_.round(result, 2), ...numberFormat) : _.round(result, 2);
			
			thisCls._insertValue(selector, calcValue);
			
			thisCls.eventListeners.items.push(e);
		});	
		
		if (twoWay) {
			$(selector).on(thisCls.inputEvent, function(e) {
				if (!stat()) return;
				let val = thisCls._valToNumber(e.target.value, 2);
				
				let result = _.round(thisCls._calc('percent_only', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				const calcValue = numberFormat ? $.number(_.round(result, 2), ...numberFormat) : _.round(result, 2);
				
				thisCls._insertValue(thisCls.mainSelector, calcValue);
				
				thisCls.eventListeners.items.push(e);
			});
		}
		
		
	}
	
	
	
	
	
	
	
	
	
	nds(data) {
		let {
			selector, // куда вставлять данные. Можно вызвать функцию: (selector, value) => $(selector).setAttrib('replacer', value)
			percent, // процент для расчета
			reverse, // в обратном направлении
			twoWay, // двухсторонняя привязка
			middleware, // промежуточный расчет значения. Пример: middleware: [(value, calc) => calc('nds', value, percentNds), false],
			numberFormat, // фрматировать вставляемое значение. Эквивалент $.number: [2, '.', ' '],
			stat // производить расчет
		} = $.extend({
			selector: null,
			percent: 0,
			reverse: false,
			twoWay: false,
			middleware: false,
			numberFormat: false,
			stat: () => true
		}, data),
			thisCls = this;
		
		$(thisCls.mainSelector).on(thisCls.inputEvent, function(e) {
			if (!stat()) return;
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('nds', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}
			
			const calcValue = numberFormat ? $.number(_.round(result, 2), ...numberFormat) : _.round(result, 2);
			
			thisCls._insertValue(selector, calcValue);
			
			thisCls.eventListeners.items.push(e);
		});
		
		if (twoWay) {
			const twoWaySelector = _.isFunction(twoWay) || _.isString(twoWay) ? twoWay : selector;
			$(twoWaySelector).on(thisCls.inputEvent, function(e) {
				if (!stat()) return;
				let val = thisCls._valToNumber(e.target.value, 2);
				let result = _.round(thisCls._calc('nds', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				const calcValue = numberFormat ? $.number(_.round(result, 2), ...numberFormat) : _.round(result, 2);
				
				thisCls._insertValue(thisCls.mainSelector, calcValue);
				
				thisCls.eventListeners.items.push(e);
			});
		}
		
	}
	
	
	
	
	
	
	
	
		count_days(data) {

		let {
			selector, // куда вставлять данные. Можно вызвать функцию: (selector, value) => $(selector).setAttrib('replacer', value)
			initialDate, // дата начала
			addWorkdays, // чекбокс вернуть все или только рабочие дня
			middleware, // промежуточный расчет значения. Пример: middleware: [(value, calc) => calc('nds', value, percentNds), false],
			numberFormat, // фрматировать вставляемое значение. Эквивалент $.number: [2, '.', ' '],
			stat // производить расчет
		} = $.extend({
			selector: null,
			initialDate: null,
			addWorkdays: 0,
			middleware: false,
			numberFormat: false,
			stat: () => true
		}, data),
			thisCls = this;

		let changeCountDaysTout;
		$(thisCls.mainSelector).on(thisCls.inputEvent, function (e) {
			clearTimeout(changeCountDaysTout);

			changeCountDaysTout = setTimeout(async () => {
				if (!stat()) return;
				let val = thisCls._valToNumber(e.target.value);
				let result = thisCls._calc('count_days', val, initialDate, addWorkdays);

				if (_.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}

				if (initialDate.value && val) {
					const dateObject = await result();
					const dh = dateObject.dateHuman;
					$(selector).find('input').attr("date", dateObject.date);
					$(selector).find('input').val(dh.day.short + ' ' + dh.month.named + ' ' + dh.year.full + ' г.');
					thisCls.eventListeners.items.push(e);
				}
			}, 300)
		});

	}
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------ Функции рассчетов
	
	
	
	_calc(method = null, ...args) {
		if (_.isNull(method)) throw Error('ddrCalc -> _calc: ошибка! Не передан метод!');
		
		switch(method) {
			case 'percent':
				let [value, percent, reverse = false] = args;
				
				value = this._valToNumber(value, 2);
				
				percent = _.isPlainObject(percent) ? percent.value : percent;
				percent = Number(percent);
				
				if (!reverse) return percent < 100 ? _.round(value / ((100 - percent) / 100), 2) : 0;
				return percent < 100 ? _.round(value * ((100 - percent) / 100), 2) : 0;
				break;
			
			case 'percent_only':
				let [valueOnly, percentOnly, reverseOnly = false] = args;
				
				valueOnly = this._valToNumber(valueOnly, 2);
				
				percentOnly = _.isPlainObject(percentOnly) ? percent.value : percentOnly;
				percentOnly = Number(percentOnly);
				
				if (!reverseOnly) return percentOnly < 100 ? _.round((valueOnly / 100) * percentOnly, 2) : 0;
				return percentOnly < 100 ? _.round(valueOnly - (valueOnly / (1 + percentOnly / 100)), 2) : 0;
				break;
			
			case 'nds':
				let [valueLess, nds, reverseLess = false] = args;
				
				valueLess = this._valToNumber(valueLess, 2);
				
				nds = _.isPlainObject(nds) ? nds.value : nds;
				nds = Number(nds);
				
				if (!reverseLess) return _.round(valueLess * (1 + nds / 100), 2);
				return _.round(valueLess / (1 + nds / 100), 2);
				break;
			
			case 'count_days':
				let [countDays, initialDate, addWorkdays] = args;
				return async () => {
					const [day, month, year] = initialDate.value.split('-');
					const initDate = new Date(year, month - 1, day);
					const { data, error, status, headers } = await axiosQuery('get', 'site/contracts/work_calendar_count', {
						year: initDate.getFullYear(),
						month: initDate.getMonth() + 1,
						day: initDate.getDate(),
						count_days: countDays,
						add_work_days: addWorkdays.value,
					}, 'json');
					const date = new Date(data);
					const dateFormated = `${date.getDate()}-${addZero(date.getMonth() + 1)}-${addZero(date.getFullYear())}`;
					return {
						date: dateFormated,
						dateHuman: ddrDateBuilder(data)
					}
				}
				break;
				
			default:
				
		  		break;
		  }
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------
	
	_valToNumber(value = null, toFixed = null) {
		if (_.isNull(value)) return false;
		value = _.round(Number((''+value).replaceAll(/\s/g, '')), toFixed);
		return value; 
	}
	
	
	
	_insertValue(selector, calcValue = '') {
		if (_.isFunction(selector)) {
			selector(calcValue);
		} else {
			const selectorTag = $(selector).prop("tagName")?.toLowerCase() || null;
			if (['input', 'select', 'textarea',].indexOf(selectorTag) !== -1) {
				$(selector).val(calcValue);
			} else {
				$(selector).text(calcValue);
			}
		}
	}
	
	
	
	
}