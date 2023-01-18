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
	
	const methods = new DdrCalc(mainSelector); 
	
	data.forEach((item) => {
		
		const {method} = item;
		
		delete(item['method']);
		
		if (['percent', 'percentLess'].indexOf(method) === -1) throw Error('ddrCalc ошибка! Такого метода не существует!');
		
		methods[method](item);
	});
	
	
	return {
		calc() {
			$(mainSelector).trigger('input');
		}
	};
};





class DdrCalc {
	
	mainSelector = null;
	
	constructor(mainSelector) {
		this.mainSelector = mainSelector;
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
			
		$(thisCls.mainSelector).on('input', function(e) {
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('percent', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}
			$(selector).val(_.round(result, 2));
		});	
		
		if (twoWay) {
			$(selector).on('input', function(e) {
				
				let val = thisCls._valToNumber(e.target.value, 2);
				
				let result = _.round(thisCls._calc('percent', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				$(thisCls.mainSelector).val(_.round(result, 2));
			});
		}
		
		
	}
	
	
	
	
	
	
	
	
	percentLess(data) {
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
			
		$(thisCls.mainSelector).on('input', function(e) {
			let val = thisCls._valToNumber(e.target.value, 2);
			let result = _.round(thisCls._calc('percentLess', val, percent, reverse), 2);
			
			if (_.isFunction(middleware[0])) {
				result = middleware[0](result, thisCls._calc.bind(thisCls));
			}
			
			$(selector).val(_.round(result, 2));
		});	
		
		
		if (twoWay) {
			$(selector).on('input', function(e) {
				let val = thisCls._valToNumber(e.target.value, 2);
				let result = _.round(thisCls._calc('percentLess', val, percent, !reverse), 2);
				
				if (middleware[1] !== undefined && middleware[1] !== false && _.isFunction(middleware[1])) {
					result = middleware[1](result, thisCls._calc.bind(thisCls));
				} else if (middleware[1] !== false && _.isFunction(middleware[0])) {
					result = middleware[0](result, thisCls._calc.bind(thisCls));
				}
				
				$(thisCls.mainSelector).val(_.round(result, 2));
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
				
				if (!reverse) return percent < 100 ? _.round(value / ((100 - percent) / 100), 2) : 0;
				return percent < 100 ? _.round(value * ((100 - percent) / 100), 2) : 0;
				break;
			
			case 'percentLess':
				let [valueLess, percentLess, reverseLess = false] = args;
				
				valueLess = this._valToNumber(valueLess, 2);
				
				percentLess = _.isPlainObject(percentLess) ? percentLess.value : percentLess;
				if (!reverseLess) return _.round(valueLess * (1 + percentLess / 100), 2);
				return _.round(valueLess / (1 + percentLess / 100), 2);
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