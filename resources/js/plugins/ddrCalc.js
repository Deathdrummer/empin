/*window.ddrCalc = (value, percent, reverse = false) => {
	value = Number(value);
	percent = Number(percent);
	
	if (reverse) return value * ((100 - percent) / 100);
	return value / ((100 - percent) / 100);
	
	if (reverse) return value / (1 + percent / 100);
	return value * (1 + percent / 100);
}
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
		
		if (['percent', 'nds'].indexOf(method) === -1) throw Error('ddrCalc ошибка! Такого метода не существует!');
		
		methods[method](item);
	});
	
	
	return {
		calc() {
			$(mainSelector).trigger('input.'+eventRandStr);
		},
		destroy() {
			eventListeners.items.forEach(function(evt) {
				$(evt.target).off('input.'+eventRandStr);
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
		this.inputEvent = 'input.'+eventRandStr;
	}
	
	
	
	percent(data) {
		let {
			selector,
			percent,
			reverse,
			twoWay,
			middleware,
		} = $.extend({
			selector: null,
			percent: 0,
			reverse: false,
			twoWay: false,
			middleware: false,
		}, data),
			thisCls = this;
			
		$(thisCls.mainSelector).on(thisCls.inputEvent, function(e) {
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('percent', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}
			$(selector).val(_.round(result, 2));
			
			thisCls.eventListeners.items.push(e);
		});	
		
		if (twoWay) {
			$(selector).on(thisCls.inputEvent, function(e) {
				
				let val = thisCls._valToNumber(e.target.value, 2);
				
				let result = _.round(thisCls._calc('percent', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				$(thisCls.mainSelector).val(_.round(result, 2));
				
				thisCls.eventListeners.items.push(e);
			});
		}
		
		
	}
	
	
	
	
	
	
	
	
	nds(data) {
		let {
			selector,
			percent,
			reverse,
			twoWay,
			middleware,
		} = $.extend({
			selector: null,
			percent: 0,
			reverse: false,
			twoWay: false,
			middleware: false,
		}, data),
			thisCls = this;
			
		$(thisCls.mainSelector).on(thisCls.inputEvent, function(e) {
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('nds', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}
			
			$(selector).val(_.round(result, 2));
			
			thisCls.eventListeners.items.push(e);
		});	
		
		
		if (twoWay) {
			$(selector).on(thisCls.inputEvent, function(e) {
				let val = thisCls._valToNumber(e.target.value, 2);
				let result = _.round(thisCls._calc('nds', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				$(thisCls.mainSelector).val(_.round(result, 2));
				
				thisCls.eventListeners.items.push(e);
			});
		}
		
		
		
		
	}
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------
	
	
	
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
			
			case 'nds':
				let [valueLess, nds, reverseLess = false] = args;
				
				valueLess = this._valToNumber(valueLess, 2);
				
				nds = _.isPlainObject(nds) ? nds.value : nds;
				nds = Number(nds);
				
				if (!reverseLess) return _.round(valueLess * (1 + nds / 100), 2);
				return _.round(valueLess / (1 + nds / 100), 2);
				break;
				
			default:
				
		  		break;
		  }
	}
	
	
	
	
	
	_valToNumber(value = null, toFixed = null) {
		if (_.isNull(value)) return false;
		value = _.round(Number((''+value).replaceAll(/\s/g, '')), toFixed);
		return value; 
	}
	
	
	
	
}