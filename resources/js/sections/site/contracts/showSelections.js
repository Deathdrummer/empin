export function showSelections(cell, contractId = null, selectionsTooltip) {
	if (_.isNull(contractId)) return false;
	
	selectionsTooltip = $(cell).ddrTooltip({
		//cls: 'w44rem',
		placement: 'right-start',
		tag: 'noscroll noopen',
		offset: [0, 0],
		minWidth: '200px',
		minHeight: '200px',
		duration: [200, 200],
		trigger: 'mouseenter focus',
		wait: {
			iconHeight: '40px'
		},
		onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
			
			const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/contract_selections', {
				contract_id: contractId, 
			}, 'json');
			
			if (error) {
				$.notify('Ошибка! Не удалось загрузить список подборок!', 'error');
				console.log(error.message);
				waitDetroy();
				return;
			}
			
			await setData(data);
			
			waitDetroy();
		}
	});
	
	
	
	// закрытие окна по нажатию клавиши ESC
	$(document).one('keydown', (e) => {
		if (e.keyCode == 27 && selectionsTooltip?.destroy != undefined) {
			selectionsTooltip.destroy();
		}
	});
	
	return selectionsTooltip;
}