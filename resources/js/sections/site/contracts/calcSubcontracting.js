export function calcSubcontracting(ops = {}) {
	
	let {percentNds, contractingPercent} = ops;
	
	const stat = () => $('#autoCalcState').is(':checked') == false;
	
	const selfPriceNds = $('#selfPriceNds').ddrCalc([{
		selector: '#genPriceNds',
		method: 'percent',
		percent: contractingPercent,
		stat
	}, {
		selector: '#genPrice',
		method: 'percent',
		percent: contractingPercent,
		middleware: [(value, calc) => {
			return calc('nds', $('#genPriceNds').val(), percentNds, true);
		}, false],
		stat
	}]);

	const selfPrice = $('#selfPrice').ddrCalc([{
		selector: '#genPrice',
		method: 'percent',
		percent: contractingPercent,
		stat
	}, {
		selector: '#genPriceNds',
		method: 'percent',
		percent: contractingPercent,
		middleware: [(value, calc) => {
			return calc('nds', $('#genPrice').val(), percentNds);
		}, false],
		stat
	}]);





	const genPriceNds = $('#genPriceNds').ddrCalc([{
		selector: '#genPrice',
		method: 'nds',
		percent: percentNds,
		reverse: true,
		stat
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		stat
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPriceNds').val(), percentNds, true);
		}, false],
		stat
	}]);

	const genPrice = $('#genPrice').ddrCalc([{
		selector: '#genPriceNds',
		method: 'nds',
		percent: percentNds,
		stat
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		stat
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPrice').val(), percentNds);
		}, false],
		stat
	}]);
	
	return {selfPriceNds, selfPrice, genPriceNds, genPrice};
}