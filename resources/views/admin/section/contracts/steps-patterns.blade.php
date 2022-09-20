<section>
	<x-card id="stepsPatternsCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w40rem"><strong>Заказчик</strong></td>
						<td></td>
						
						<td class="w10rem"><strong>Установки</strong></td>
						<td class="w10rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="stepsPatternsList"></tbody>
				<tfoot>
					<tr>
						<td colspan="8" class="right">
							<x-button id="stepsPatternAddBtn" onclick="$.stepsPatternAdd(this)" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>





<script type="module">
	$.ddrCRUD({
		container: '#stepsPatternsList',
		itemToIndex: 'tr',
		route: 'ajax/steps_patterns',
		viewsPath: 'admin.section.contracts.render.steps-patterns',
	}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, getParams}) => {
		
		$('#stepsPatternsCard').card('ready');
		
		if (error) {
			$.notify(error.message, 'error');
			return false;
		}  
		
		$('#stepsPatternAddBtn').ddrInputs('enable');
		
		changeInputs({'[save], [update]': 'enable'});
		
		
		
		$.stepsPatternAdd = (btn) => {
			let stepsPatternAddBtnWait = $(this).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			create((data, container, {error}) => {
				stepsPatternAddBtnWait.destroy();
				if (data) $(container).append(data);
				if (error) $.notify(error.message, 'error');
			});
		}
		
		
		
		$.stepsPatternSave = (btn) => {
			let row = $(btn).closest('tr');
			
			let stepsPatternSaveWait = $(row).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffffd6'
			});
			
			storeWithShow(row, (data, container, {error}) => {
				if (data) {
					$(row).replaceWith(data);
					$.notify('Запись успешно сохранена!');
				}
				
				if (error) {
					stepsPatternSaveWait.destroy();
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
			});
		}
		
		
		$.stepsPatternUpdate = (btn, id) => {
			let row = $(btn).closest('tr');
			
			update(id, row, (data, container, {error}) => {
				if (data) {
					$(row).find('[update]').ddrInputs('disable');
					$(row).find('input, select, textarea').ddrInputs('state', 'clear');
					$.notify('Запись успешно обновлена!');
				}
				
				if (error) $.notify(error.message, 'error');
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
			});
		}
		
		
		
		$.stepsPatternRemove = (btn, id) => {
			let row = $(btn).closest('tr');
			
			if (!id) {
				$(row).remove();
			} else {
				let removeStepsPatternPopup = ddrPopup({
					width: 400, // ширина окна
					lhtml: 'dialog.delete', // контент
					buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'stepsPatternRemoveAction:'+id}],
					centerMode: true,
					winClass: 'ddrpopup_dialog color-red'
				});
				
				removeStepsPatternPopup.then(({close, wait}) => {
					$.stepsPatternRemoveAction = (btn) => {
						wait();
						destroy(id, function(stat) {
							if (stat) {
								remove(row);
								$.notify('Запись успешно удалена!');
							} else {
								$.notify('Ошибка удаления записи!', 'error');
							} 
							close();
						});
					}
				});	
			}
		}
		
		
		
		
		
		
		
		
		
		
		
		//------------------------------------------------------------------------------ Этапы
		$.departmentSteps = (btn, customerId, customerName) => {
			ddrPopup({
				title: '<small class="color-gray">Установки:</small> '+customerName,
				width: 1200,
				buttons: ['Отмена', {action: 'stepsPatternsSave', title: 'Сохранить'}],
				disabledButtons: true,
				buttonsGroup: 'small',
			}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
				wait();
				
				const views = 'admin.section.contracts.render.steps-patterns';
				
				axiosQuery('get', 'ajax/steps_patterns/steps', {views, customer: customerId}).then(({data, error, status, headers}) => {
					if (error) {
						console.log(error?.message, error?.errors);
						$.notify('Ошибка получения установок шаблона', 'error');
						return;
					}
					
					enableButtons('close');
					
					setHtml(data, () => {
						let ebStat = false;
						$('#stepsPatternStepsBlock').ddrInputs('change', () => {
							if (!ebStat) {
								enableButtons(true);
								ebStat = true;
							}
						});
					});
						
				}).catch((e) => {
					console.log(e);
				});
				
				$.stepsPatternsSave = (__) => {
					wait();
					let checkedSteps = $('#stepsPatternStepsBlock').find('[stepcheck]:checked');
					
					let stepsData = {};
					if (checkedSteps.length) {
						$.each(checkedSteps, (k, item) => {
							let d = $(item).attr('stepcheck').split('|'),
								dept = d[0] || null,
								step = d[1] || null;
							
							if (!stepsData[dept]) stepsData[dept] = [];
							stepsData[dept].push(parseInt(step));
						});
					}
					
					if (stepsData) {
						axiosQuery('post', 'ajax/steps_patterns/steps', {stepsData, customer: customerId}, 'json').then(({data, error, status, headers}) => {
							if (error) {
								console.log(error?.message, error?.errors);
								$.notify('Ошибка сохранения установок шаблона', 'error');
								wait(false);
								return;
							}
							
							if (data) {
								$.notify('Установки шаблона успешно сохранены');
								close();
							}
						}).catch((e) => {
							console.log(e);
							wait(false);
						});
					}
				}
				
				
				
				let sbAllStat = false;
				$.stepsPatternChooseAll = (btn) => {
					let checks = $(btn).closest('[depblock]').find('[stepslist]').find('input[type="checkbox"]');
					
					if (!sbAllStat) {
						enableButtons(true);
						sbAllStat = true;
					}
					
					if ($(checks).length == $(checks).filter(':checked').length) {
						$(checks).ddrInputs('checked', false);
					} else {
						$(checks).ddrInputs('checked');
					}
				}
				
				
			});
		}
		
	});
	
	
	
	
</script>