export function calcGencontracting(ops = {}) {
	
	let {percentNds, contractingPercent} = ops;
	
	const stat = () => $('#autoCalcState').is(':checked') == false;
	
	const selfPriceNds = $('#selfPriceNds').ddrCalc([{
		selector: '#subPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		stat
	}, {
		selector: '#subPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#subPriceNds').val(), percentNds, true);
		}, false],
		stat
	}]);
	
	const selfPrice = $('#selfPrice').ddrCalc([{
		selector: '#subPrice',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		stat
	}, {
		selector: '#subPriceNds',
		method: 'percent',
		percent: contractingPercent,
		reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#subPrice').val(), percentNds);
		}, false],
		stat
	}]);
	
	
	
	
	
	const subPriceNds = $('#subPriceNds').ddrCalc([{
		selector: '#subPrice',
		method: 'nds',
		percent: percentNds,
		reverse: true,
		stat
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
		stat
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPriceNds').val(), percentNds, true);
		}, false],
		stat
	}]);
	
	const subPrice = $('#subPrice').ddrCalc([{
		selector: '#subPriceNds',
		method: 'nds',
		percent: percentNds,
		stat
	}, {
		selector: '#selfPrice',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
		stat
	}, {
		selector: '#selfPriceNds',
		method: 'percent',
		percent: contractingPercent,
		//reverse: true,
		middleware: [(value, calc) => {
			return calc('nds', $('#selfPrice').val(), percentNds);
		}, false],
		stat
	}]);
	
	return {selfPriceNds, selfPrice, subPriceNds, subPrice};
}