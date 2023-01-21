export function calcGencontracting(ops = {}) {
	
	let {percentNds, contractingPercent} = ops;
	
	const selfPriceNds = $('#selfPriceNds').ddrCalc([{
		selector: '#subPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
	}, {
		selector: '#subPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#subPriceNds').val(), percentNds, true);
		}, false],
	}]);
	
	const selfPrice = $('#selfPrice').ddrCalc([{
		selector: '#subPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
	}, {
		selector: '#subPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#subPrice').val(), percentNds);
		}, false],
	}]);
	
	
	
	
	
	const subPriceNds = $('#subPriceNds').ddrCalc([{
		selector: '#subPrice',
		method: 'nds',
		percent: percentNds,
		reverse: true,
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPriceNds').val(), percentNds, true);
		}, false],
	}]);
	
	const subPrice = $('#subPrice').ddrCalc([{
		selector: '#subPriceNds',
		method: 'nds',
		percent: percentNds,
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPrice').val(), percentNds);
		}, false],
	}]);
	
	return {selfPriceNds, selfPrice, subPriceNds, subPrice};
}