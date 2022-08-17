<section>
	<x-card
		id="contractsCard"
		title="Список договоров"
		button="Создать договор"
		buttonId="contractAddBtn"
		disableBtn
		action="contractNew"
		loading
		>
		
		<div class="mt2rem">
			<x-chooser class="h24px" variant="neutral" px="20">
				<x-chooser.item action="getListAction" active>Все</x-chooser.item>
				<x-chooser.item action="getListAction:0">В работе</x-chooser.item>
				<x-chooser.item action="getListAction:1">Архив</x-chooser.item>
			</x-chooser>
		</div>
		
		<div class="table mt2rem">
			<table>
				<thead>
					<tr>
						<td class="w8rem"><strong class="lh90 d-block">Номер объекта</strong></td>
						<td class="w20rem"><strong>Название / заявитель</strong></td>
						<td><strong>Титул</strong></td>
						<td class="w20rem"><strong>Заказчик</strong></td>
						<td class="w20rem"><strong>Населенный пункт</strong></td>
						<td class="w14rem"><strong class="lh90 d-block">Стоимость договора</strong></td>
						<td class="w16rem"><strong>Дата создания</strong></td>
						<td class="w7rem"><strong>Архив</strong></td>
						<td class="w9rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="contractsList"></tbody>
				{{-- <tfoot>
					<tr>
						<td colspan="9" class="right">
							тут будет пагинация
						</td>
					</tr>
				</tfoot> --}}
			</table>
		</div>
	</x-card>
</section>




<script type="module">
	$.ddrCRUD({
		container: '#contractsList',
		itemToIndex: 'tr',
		route: 'ajax/contracts',
		//params: {
		//	list: {archive: 0/*department_id: deptId*/},
		//	//store: {department_id: deptId},
		//},
		viewsPath: 'admin.section.contracts.render.contracts',
	}).then(({error, list, changeInputs, create, store, storeWithShow, edit, update, destroy, query, getParams, abort, remove}) => {
		
		$('#contractsCard').card('ready');
		
		if (error) {
			$.notify(error.message, 'error');
			return false;
		}  
		
		$('#contractAddBtn').ddrInputs('enable');
		
		
		
		let isLoadingFilters = false, currentListStat = null/*, choosed = []*/;
		$.getListAction = (btn, isActive, stat) => {
			//choosed = storeArray(choosed, stat, !isActive);
			
			if (isActive) return false;
			
			currentListStat = stat;
			
			if (isLoadingFilters) {
				isLoadingFilters = false;
				abort();
			}
			
			let departmentAddBtnWait = $('#contractsList').ddrWait({
				iconHeight: '40px',
				text: 'Загрузка',
				fontSize: '14px',
				bgColor: '#ffffffbb'
			});

			isLoadingFilters = true;
			list({archive: stat}, function(stat) {
				isLoadingFilters = false;
				departmentAddBtnWait.destroy();
			});
		}
		
		
		
		
		
		
		$.contractNew = () => {
			ddrPopup({
				title: 'Новый договор',
				width: 1300,
				buttons: ['Отмена', {action: 'contractStore', title: 'Создать'}],
				disabledButtons: true, // при старте все кнопки кроме закрытия будут disabled
				closeByBackdrop: false, // Закрывать окно по фону либо только по [ddrpopupclose]
				winClass: 'ddrpopup_white'
			}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
				wait();
				
				create((data, container, {error}) => {
					wait(false);
					if (data) setHtml(data, () => {
						enableButtons('close');
						$('input[name="price"]').number(true, 2, '.', ' ');
						$('#contractForm').ddrInputs('change', function(item) {
							enableButtons(false);
						});
					});
					if (error) $.notify(error.message, 'error');
				});
				
				$.contractStore = (btn) => {
					wait();
					
					let form = $('#contractForm');
					
					storeWithShow(form, (data, container, {error}) => {
						if (data) {
							$('#contractsList').append(data);
							$.notify('договор успешно создан!');
							close();
						}
						
						if (error) {
							$.notify(error.message, 'error');
							wait(false);
						} 
						
						if (error.errors) {
							console.log(error.errors);
							$.each(error.errors, function(field, errors) {
								$(form).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
							});
						}
					});
				}
			});	
		}
		
		
		
		
		
		
		$.contractShow = (btn, id, objectId) => {
			ddrPopup({
				title: 'Договор '+objectId,
				width: 1300,
				buttons: ['Отмена', {action: 'contractUpdate:'+id, title: 'Обновить', id: 'updateContractBtn'}],
				disabledButtons: true, // при старте все кнопки кроме закрытия будут disabled
				closeByBackdrop: false, // Закрывать окно по фону либо только по [ddrpopupclose]
				winClass: 'ddrpopup_white'
			}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
				wait();
				
				
				edit(id, (data, container, {error}) => {
					wait(false);
					
					if (data) {
						setHtml(data, () => {
							enableButtons('close');
							$('input[name="price"]').number(true, 2, '.', ' ');
							$('#contractForm').ddrInputs('change', function(item) {
								enableButtons(false);
							});
						});
					} 
					if (error) $.notify(error.message, 'error');
				});
				
				
				$.contractUpdate = (_, id) => {
					wait();
					let row = $(btn).closest('tr'),
						form = $('#contractForm');
					
					update(id, form, (data, container, {error}) => {
						if (data) {
							$.notify('Запись успешно обновлена!');
							$(row).replaceWith(data);
							close();
						}
						
						if (error) {
							wait(false);
							$.notify(error.message, 'error');
						} 
						
						if (error.errors) {
							$.each(error.errors, function(field, errors) {
								$(form).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
							});
						}
					});
				}
				
			});	
		}
		
		
		
		// depblock - основной блок (вмещает все)
		// stepslist - список чекбоксов
		// stepsrow - блок чекбокса
		// stepcheck - чекбокс
		// stepassignedselect - вып. список
		// stepdeadline - дедлайн
		// showindepartment - чекбокс (показать в отделе)
		
		$.contractChooseAllSteps = (btn) => {
			let checks = $(btn).closest('[depblock]').find('[stepslist]').find('input[type="checkbox"]'),
				stepassignedselect = $(btn).closest('[depblock]').find('[stepassignedselect]'), 
				showindepartment = $(btn).closest('[depblock]').find('[showindepartment]'),
				stepdeadline = $(btn).closest('[depblock]').find('[stepdeadline]');
			
			if ($(checks).length == $(checks).filter(':checked').length) {
				$(checks).ddrInputs('checked', false);
				$(showindepartment).ddrInputs('disable');
				$(showindepartment).ddrInputs('checked', false);
				$(stepassignedselect).ddrInputs('disable');
				$(stepassignedselect).ddrInputs('selected', false);
				$(stepdeadline).ddrInputs('disable');
			} else {
				$(checks).ddrInputs('checked'); 
				$(showindepartment).ddrInputs('enable');
				$(stepdeadline).ddrInputs('enable');
				$(stepassignedselect).ddrInputs('enable');
			}
		}
		
		
		
		$.chechStep = (checkbox, assignCheck) => {
			let stepassignedselect = $(checkbox).closest('[stepsrow]').find('[stepassignedselect]'), 
				stepdeadline = $(checkbox).closest('[stepsrow]').find('[stepdeadline]'),
				showindepartment = $(checkbox).closest('[depblock]').find('[showindepartment]'),
				countChecked = $(checkbox).closest('[stepslist]').find('[stepcheck]:checked').length;
			
			if ($(checkbox).is(':checked')) {
				$(stepdeadline).ddrInputs('enable');
			} else {
				$(stepdeadline).ddrInputs('disable');
			}
			
			if (assignCheck) {
				if ($(checkbox).is(':checked')) {
					$(stepassignedselect).ddrInputs('enable');
					$(stepdeadline).ddrInputs('enable');
				} else {
					$(stepassignedselect).ddrInputs('disable');
					$(stepassignedselect).ddrInputs('selected', false);
					$(stepdeadline).ddrInputs('disable');
				}
			}
			
			if (countChecked) {
				$(showindepartment).ddrInputs('enable');
			} else {
				$(showindepartment).ddrInputs('disable');
				$(showindepartment).ddrInputs('checked', false);
			}
		}
		
		
		
		
		
		$.contractToArchive = (btn, id) => {
			let row = $(btn).closest('tr'),
				checked = $(btn).is(':checked') ? 1 : 0;
			
			query({method: 'get', route: 'to_archive/'+id, data: {checked}}, function(stat) {
				if (stat) {
					if (currentListStat !== null) remove(row);
					$.notify('Договор успешно отправлен в архив!');
				} else {
					$.notify('Ошибка! Договор не был отправлен в архив!', 'error');
				}
			});
		}
		
		
		
		
		
		$.contractRemove = (btn, id) => {
			let row = $(btn).closest('tr'),
				removeContractPopup = ddrPopup({
					width: 400, // ширина окна
					lhtml: 'dialog.delete', // контент
					buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'contractRemoveAction:'+id}],
					centerMode: true,
					winClass: 'ddrpopup_dialog color-red'
				});
				
			removeContractPopup.then(({close, wait}) => {
				$.contractRemoveAction = (_) => {
					wait();
					destroy(id, function(stat) {
						if (stat) {
							remove(btn);
							$.notify('Договор успешно удален!');
						} else {
							$.notify('Ошибка удаления договора!', 'error');
						} 
						close();
					});
				}
			});	
		}
		
	});
	
	
</script>