export function calcSubcontracting(ops = {}) {
	
	let {percentNds, contractingPercent} = ops;
	
	const selfPriceNds = $('#selfPriceNds').ddrCalc([{
		selector: '#genPriceNds',
		method: 'percent',
		percent: contractingPercent,
	}, {
		selector: '#genPrice',
		method: 'percent',
		percent: contractingPercent,
		middleware: [(value, calc) => {
			return calc('nds', $('#genPriceNds').val(), percentNds, true);
		}, false],
	}]);

	const selfPrice = $('#selfPrice').ddrCalc([{
		selector: '#genPrice',
		method: 'percent',
		percent: contractingPercent,
	}, {
		selector: '#genPriceNds',
		method: 'percent',
		percent: contractingPercent,
		middleware: [(value, calc) => {
			return calc('nds', $('#genPrice').val(), percentNds);
		}, false],
	}]);





	const genPriceNds = $('#genPriceNds').ddrCalc([{
		selector: '#genPrice',
		method: 'nds',
		percent: percentNds,
		reverse: true,
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPriceNds').val(), percentNds, true);
		}, false],
	}]);

	const genPrice = $('#genPrice').ddrCalc([{
		selector: '#genPriceNds',
		method: 'nds',
		percent: percentNds,
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPrice').val(), percentNds);
		}, false],
	}]);
	
	return {selfPriceNds, selfPrice, genPriceNds, genPrice};
}