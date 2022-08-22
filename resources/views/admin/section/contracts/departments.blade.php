<section>
	<x-card id="departmentsCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w30rem"><strong>Название</strong></td>
						<td></td>
						<td class="w9rem" title="Отобразить только для назначенных"><strong>Отобр. только для назн.</strong></td>
						<td class="w8rem" title="Сортировка"><strong>Сорт.</strong></td>
						<td class="w6rem"><strong>Этапы</strong></td>
						<td class="w10rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="departmentsList"></tbody>
				<tfoot>
					<tr>
						<td colspan="8" class="right">
							<x-button id="departmentAddBtn" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>






<script type="module">
	
	$.ddrCRUD({
		container: '#departmentsList',
		itemToIndex: 'tr',
		route: 'ajax/departments',
		viewsPath: 'admin.section.contracts.render.departments',
	}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, getParams}) => {
		
		$('#departmentsCard').card('ready');
		
		if (error) {
			$.notify(error.message, 'error');
			return false;
		}  
		
		$('#departmentAddBtn').ddrInputs('enable');
		
		changeInputs({'[save], [update]': 'enable'});
		
		
		$('#departmentAddBtn').on(tapEvent, function() {
			let departmentAddBtnWait = $(this).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffff91'
			});
			
			create((data, container, {error}) => {
				departmentAddBtnWait.destroy();
				if (data) $(container).append(data);
				if (error) $.notify(error.message, 'error');
			});
		});
		
		
		$.departmentSave = (btn) => {
			let row = $(btn).closest('tr');
			
			let departmentSaveWait = $(row).ddrWait({
				iconHeight: '26px',
				bgColor: '#ffffffd6'
			});
			
			storeWithShow(row, (data, container, {error}) => {
				if (data) {
					$(row).replaceWith(data);
					$.notify('Запись успешно сохранена!');
				}
				
				if (error) {
					departmentSaveWait.destroy();
					$.notify(error.message, 'error');
				} 
				
				if (error.errors) {
					$.each(error.errors, function(field, errors) {
						$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
					});
				}
			});
		}
		
		
		$.departmentUpdate = (btn, id) => {
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
		
		
		
		$.departmentRemove = (btn, id) => {
			let row = $(btn).closest('tr');
			
			if (!id) {
				$(row).remove();
			} else {
				let removeDepartmentPopup = ddrPopup({
					width: 400, // ширина окна
					lhtml: 'dialog.delete', // контент
					buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'departmentRemoveAction:'+id}],
					centerMode: true,
					winClass: 'ddrpopup_dialog color-red'
				});
				
				removeDepartmentPopup.then(({close, wait}) => {
					$.departmentRemoveAction = (btn) => {
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
		$.departmentSteps = (btn, deptId) => {
			ddrPopup({
				url: 'ajax/steps/init',
				params: {views: 'admin.section.contracts.render.steps'},
				title: 'Этапы',
				width: 800,
				//frameOnly: true,
				//buttons, // массив кнопок
				//buttonsAlign, // выравнивание вправо
				//disabledButtons, // при старте все кнопки кроме закрытия будут disabled
				buttonsGroup: 'small', // группа для кнопок
				//winClass, // добавить класс к модальному окну
				//centerMode, // контент по центру
				//topClose // верхняя кнопка закрыть
			}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
				wait();
				//onClose(() => {
				//	console.log('closing');
				//});
				
				$.ddrCRUD({
					container: '#stepsList',
					itemToIndex: 'tr',
					route: 'ajax/steps',
					params: {
						list: {department_id: deptId},
						store: {department_id: deptId},
					},
					viewsPath: 'admin.section.contracts.render.steps',
				}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, getParams}) => {
					wait(false);
					
					if (error) {
						$.notify(error.message, 'error');
						return false;
					}  
					
					$('#stepAddBtn').ddrInputs('enable');
					
					changeInputs({'[save], [update]': 'enable'});
					
					
					$.stepAdd = () => {
						let stepAddBtnWait = $(this).ddrWait({
							iconHeight: '26px',
							bgColor: '#ffffff91'
						});
						
						create((data, container, {error}) => {
							stepAddBtnWait.destroy();
							if (data) $(container).append(data);
							if (error) $.notify(error.message, 'error');
						});
					}
					
					
					
					$.stepSave = (btn) => {
						let row = $(btn).closest('tr');
						
						let stepSaveWait = $(row).ddrWait({
							iconHeight: '26px',
							bgColor: '#ffffffd6'
						});
						
						storeWithShow(row, (data, container, {error}) => {
							if (data) {
								$(row).replaceWith(data);
								$.notify('Запись успешно сохранена!');
							}
							
							if (error) {
								stepSaveWait.destroy();
								$.notify(error.message, 'error');
							} 
							
							if (error.errors) {
								$.each(error.errors, function(field, errors) {
									$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
								});
							}
						});
					}
					
					
					$.stepUpdate = (btn, id) => {
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
					
					
					
					$.stepRemove = (btn, id) => {
						let row = $(btn).closest('tr');
						
						if (!id) {
							remove(row);
						} else {
							let removeStepPopup = ddrPopup({
								width: 400, // ширина окна
								lhtml: 'dialog.delete', // контент
								buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'stepRemoveAction:'+id}],
								centerMode: true,
								winClass: 'ddrpopup_dialog color-red'
							});
							
							removeStepPopup.then(({close, wait}) => {
								$.stepRemoveAction = (btn) => {
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
					
				});
				
			});
		}
		
	});
	
</script>