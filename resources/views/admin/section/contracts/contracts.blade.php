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
						<td class="w18rem"><strong>Название / заявитель</strong></td>
						<td><strong>Титул</strong></td>
						<td><strong>Заявитель</strong></td>
						<td><strong>Номер договора</strong></td>
						<td class="w20rem"><strong>Заказчик</strong></td>
						<td class="w20rem"><strong>Населенный пункт</strong></td>
						<td class="w14rem"><strong class="lh90 d-block">Стоимость договора с НДС</strong></td>
						<td class="w14rem"><strong>Дата создания</strong></td>
						<td class="w7rem"><strong>Архив</strong></td>
						<td class="w13rem center"><strong>Действия</strong></td>
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
	
	const {calcSubcontracting, calcGencontracting} = loadSectionScripts({section: 'contracts'});
	
	const viewsPath = 'admin.section.contracts.render.contracts';
	
	$.ddrCRUD({
		container: '#contractsList',
		itemToIndex: 'tr',
		route: 'ajax/contracts',
		params: {
			//list: {archive: 0/*department_id: deptId*/},
			create: {guard: 'admin'},
			edit: {guard: 'admin'}
			//store: {department_id: deptId},
		},
		viewsPath,
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
				
				create((data, container, {error, headers}) => {
					wait(false);
					if (data) setHtml(data, () => {
						enableButtons('close');
						$('#selfPrice').number(true, 2, '.', ' ');
						$('#selfPriceNds').number(true, 2, '.', ' ');
						$('#genPrice').number(true, 2, '.', ' ');
						$('#genPriceNds').number(true, 2, '.', ' ');
						$('#subPrice').number(true, 2, '.', ' ');
						$('#subPriceNds').number(true, 2, '.', ' ');
						
						
						
						// --------------------------------------------------- Работа с НДС
						const percentNds = $('input[name="nds"]').val();
						const contractingPercent = ref($('#contractingPercent').val());
						const genpercentVariant = headers['x-genpercent'] || 'gen';
						
						const selfNds = $('#selfPrice').ddrCalc([{
							selector: '#selfPriceNds',
							method: 'nds',
							percent: percentNds,
							twoWay: true
						}]);
						
						let genSelfPriceNds, genSelfPrice, subSelfPriceNds, subSelfPrice, genPriceNds, genPrice, subPriceNds, subPrice;
						
						$('#subcontracting').on('change slavechange', function(e) {
							if (e.type == 'change') $('#gencontracting').trigger('slavechange');
							else if (e.type == 'slavechange') {
								if ($(this).is(':checked')) $(this).ddrInputs('checked', false);
								else return;
							}
							
							if ($(this).is(':checked')) {
								const calcSubData = calcSubcontracting({percentNds, contractingPercent});
								
								genSelfPriceNds = calcSubData.selfPriceNds;
								genSelfPrice = calcSubData.selfPrice;
								genPriceNds = calcSubData.genPriceNds;
								genPrice = calcSubData.genPrice;
								
								if (e.type == 'change') genSelfPriceNds.calc();
								
								$('#contractingPercent').on('input.calc', function(e) {
									contractingPercent.value = e.target.value;
									genPriceNds.calc();
								});
								
								$('[genfields]').removeAttrib('hidden');
								$('[contractingpercent]').removeAttrib('hidden');
								
							} else {
								genSelfPriceNds.destroy();
								genSelfPrice.destroy();
								genPriceNds.destroy();
								genPrice.destroy();
								
								$('[genfields]').setAttrib('hidden');
								
								$('[genfields]').find('input[name^="price_"]').val('');
								
								if (!$('#gencontracting').is(':checked')) $('[contractingpercent]').setAttrib('hidden');
								$('#calcForm').find('input').ddrInputs('state', 'clear');
								$('#calcDates').find('[date]').ddrInputs('clear');
								$('#contractingPercent').off('.calc');
							}
						});
						
						
						
						
						
						$('#gencontracting').on('change slavechange', function(e) {
							if (e.type == 'change'/* && $('#subcontracting').is(':checked')*/) $('#subcontracting').trigger('slavechange');
							else if (e.type == 'slavechange') {
								if ($(this).is(':checked')) $(this).ddrInputs('checked', false);
								else return;
							}
							
							if ($(this).is(':checked')) {
								const calcGenData = calcGencontracting({percentNds, contractingPercent});
								
								subSelfPriceNds = calcGenData.selfPriceNds;
								subSelfPrice = calcGenData.selfPrice;
								subPriceNds = calcGenData.subPriceNds;
								subPrice = calcGenData.subPrice;
								
								if (e.type == 'change') subSelfPriceNds.calc();
								
								$('#contractingPercent').on('input.calc', function(e) {
									contractingPercent.value = e.target.value;
									subSelfPriceNds.calc();
								});
								
								$('[subfields]').removeAttrib('hidden');
								$('[contractingpercent]').removeAttrib('hidden');
							
							} else {
								subSelfPriceNds.destroy();
								subSelfPrice.destroy();
								subPriceNds.destroy();
								subPrice.destroy();
								
								$('[subfields]').setAttrib('hidden');
								
								$('[subfields]').find('input[name^="price_"]').val('');
								
								if (!$('#subcontracting').is(':checked')) $('[contractingpercent]').setAttrib('hidden');
								$('#calcForm').find('input').ddrInputs('state', 'clear');
								$('#calcDates').find('[date]').ddrInputs('clear');
								$('#contractingPercent').off('.calc');
							}
						});
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						
						$('#objectNumber').ddrInputs('change', function(item) {
							let value = $(item).val(),
								number = parseInt(value),
								sliceNumber = parseInt(String(number).slice(0, 5));
								
							$(item).val(String(sliceNumber).padStart(5, '0'));
						});
						
						
						
						
						$('#withoutBuyCheck').on('change', function() {
							let isChecked = $(this).is(':checked');
							if (isChecked) {
								$('input[name="buy_number"]').ddrInputs('disable');
								$('input[name="buy_number"]').val('БЕЗ ЗАКУПКИ');
								$('input[name="buy_number"]').ddrInputs('state', 'clear');
								
								$('#dateBuyField').ddrInputs('disable');
								$('#dateBuyField').val('');
								$('#dateBuyField').removeAttrib('date');
								$('#dateBuyFieldhidden').val('');
								
							} else {
								$('input[name="buy_number"]').ddrInputs('enable');
								$('input[name="buy_number"]').val('');
								$('#dateBuyField').ddrInputs('enable');
							}
						});
						
						
						
						let isEnabledBtns = false;
						$('#contractForm').ddrInputs('change', function(item) {
							if (!isEnabledBtns) {
								enableButtons(false);
								isEnabledBtns = true;
							}
						});
						
						
						$('#contractCustomer').on('input', (input) => {
							let customer = $(input.target).val();
							$(input.target).ddrInputs('disable');
							
							let contractFormDepsStepsWait = $('#contractFormDepsSteps').ddrWait();
							
							
							axiosQuery('get', 'ajax/contracts/set_customer_rules', {customer}, 'json').then(({data, error, status, headers}) => {
								$(input.target).ddrInputs('enable');
								
								if (data) {
									$('#contractFormDepsSteps').find('[stepcheck]').ddrInputs('checked', false);
									$('#contractFormDepsSteps').find('[stepassignedselect]').ddrInputs('selected', false);
									$('#contractFormDepsSteps').find('[stepassignedselect], [stepdeadline]').ddrInputs('state', 'clear');
									
									$('#contractFormDepsSteps').find('[stepdeadline]').ddrInputs('disable');
									$('#contractFormDepsSteps').find('[stepassignedselect]').ddrInputs('disable');
									
									$('#contractFormDepsSteps').find('[showindepartment]').ddrInputs('checked', false);
									$('#contractFormDepsSteps').find('[showindepartment]').ddrInputs('disable');
									
									
									$.each(data, (dept, steps) => {
										$.each(steps, (k, step) => {
											$('[stepcheck="'+dept+'|'+step+'"]').ddrInputs('checked');
											$('[stepassignedselect="'+dept+'|'+step+'"]').ddrInputs('enable');
											$('[stepdeadline="'+dept+'|'+step+'"]').ddrInputs('enable');
										});
										
										
										let showindepartment = $('[stepcheck^="'+dept+'|"]').closest('[depblock]').find('[showindepartment]');
										$(showindepartment).ddrInputs('enable');
									});
									
								}
								
								contractFormDepsStepsWait.destroy();
								
							}).catch((e) => {
								console.log(e);
								$(input.target).ddrInputs('enable');
							});
						});
						
						
					});
					if (error) $.notify(error.message, 'error');
				});
				
				$.contractStore = (btn) => {
					wait();
					$('#genPercentFormField').val($('#genPercent:visible').val() || $('#subPercent:visible').val());
					let form = $('#contractForm');
					
					storeWithShow(form, (data, container, {error}) => {
						if (data) {
							$('#contractsList').prepend(data); // настроить, если нужно вставлять запись в начало или конец списка
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
		
		
		
		
		
		//----------------------------------------------------------------------------------------- Редактирование договора
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
				
				edit(id, (data, container, {error, headers}) => {
					if (error) {
						console.log(error);
						$.notify('Ошибка открытия договора!', 'error');
						close();
						return;
					}
					
					if (data) {
						setHtml(data, () => {
							enableButtons('close');
							wait(false);
							$('#selfPrice').number(true, 2, '.', ' ');
							$('#selfPriceNds').number(true, 2, '.', ' ');
							$('#genPrice').number(true, 2, '.', ' ');
							$('#genPriceNds').number(true, 2, '.', ' ');
							$('#subPrice').number(true, 2, '.', ' ');
							$('#subPriceNds').number(true, 2, '.', ' ');
							
							
							// --------------------------------------------------- Работа с НДС
							const percentNds = $('input[name="nds"]').val();
							const contractingPercent = ref($('#contractingPercent').val());
							const genpercentVariant = headers['x-genpercent'] || 'gen';
							

							const selfNds = $('#selfPrice').ddrCalc([{
								selector: '#selfPriceNds',
								method: 'nds',
								percent: percentNds,
								twoWay: true
							}]);
							
							//selfNds.calc(); // для админки (редактирование договора)
							
							
							let genSelfPriceNds, genSelfPrice, subSelfPriceNds, subSelfPrice, genPriceNds, genPrice, subPriceNds, subPrice;
							
							$('#subcontracting').on('change slavechange triggercontracting', function(e) {
								if (e.type == 'change') $('#gencontracting').trigger('slavechange');
								else if (e.type == 'slavechange') {
									if ($(this).is(':checked')) $(this).ddrInputs('checked', false);
									else return;
								} else if (e.type == 'triggercontracting') {
									if (!$(this).is(':checked')) $(this).ddrInputs('checked', true);
								}
								
								if ($(this).is(':checked')) {
									const calcSubData = calcSubcontracting({percentNds, contractingPercent});
									
									genSelfPriceNds = calcSubData.selfPriceNds;
									genSelfPrice = calcSubData.selfPrice;
									genPriceNds = calcSubData.genPriceNds;
									genPrice = calcSubData.genPrice;
									
									if (e.type == 'change') genSelfPriceNds.calc();
									
									$('#contractingPercent').on('input.calc', function(e) {
										contractingPercent.value = e.target.value;
										genPriceNds.calc();
									});
									
									if (e.type !== 'triggercontracting') {
										$('[genfields]').removeAttrib('hidden');
										$('[contractingpercent]').removeAttrib('hidden');
									}
									
								} else {
									genSelfPriceNds.destroy();
									genSelfPrice.destroy();
									genPriceNds.destroy();
									genPrice.destroy();
									
									
									$('[genfields]').setAttrib('hidden');
									
									$('[genfields]').find('input[name^="price_"]').val('');
									
									if (!$('#gencontracting').is(':checked')) $('[contractingpercent]').setAttrib('hidden');
									$('#calcForm').find('input').ddrInputs('state', 'clear');
									$('#calcDates').find('[date]').ddrInputs('clear');
									$('#contractingPercent').off('.calc');
								}
							});
							
							
							
							
							
							$('#gencontracting').on('change slavechange triggercontracting', function(e) {
								if (e.type == 'change'/* && $('#subcontracting').is(':checked')*/) $('#subcontracting').trigger('slavechange');
								else if (e.type == 'slavechange') {
									if ($(this).is(':checked')) $(this).ddrInputs('checked', false);
									else return;
								} else if (e.type == 'triggercontracting') {
									if (!$(this).is(':checked')) $(this).ddrInputs('checked', true);
								}
								
								if ($(this).is(':checked')) {
									const calcGenData = calcGencontracting({percentNds, contractingPercent});
									
									subSelfPriceNds = calcGenData.selfPriceNds;
									subSelfPrice = calcGenData.selfPrice;
									subPriceNds = calcGenData.subPriceNds;
									subPrice = calcGenData.subPrice;
									
									if (e.type == 'change') subSelfPriceNds.calc();
									
									$('#contractingPercent').on('input.calc', function(e) {
										contractingPercent.value = e.target.value;
										subSelfPriceNds.calc();
									});
									
									if (e.type !== 'triggercontracting') {
										$('[subfields]').removeAttrib('hidden');
										$('[contractingpercent]').removeAttrib('hidden');
									}
									
								} else {
									subSelfPriceNds.destroy();
									subSelfPrice.destroy();
									subPriceNds.destroy();
									subPrice.destroy();
									
									$('[subfields]').setAttrib('hidden');
									
									$('[subfields]').find('input[name^="price_"]').val('');
									
									if (!$('#subcontracting').is(':checked')) $('[contractingpercent]').setAttrib('hidden');
									$('#calcForm').find('input').ddrInputs('state', 'clear');
									$('#calcDates').find('[date]').ddrInputs('clear');
									$('#contractingPercent').off('.calc');
								}
							});
							
							
							
							if ($('#subcontracting').is(':checked')) {
								$('#subcontracting').trigger('triggercontracting');
							} else if ($('#gencontracting').is(':checked')) {
								$('#gencontracting').trigger('triggercontracting');
							}
							
								
							
							
							
							
							
							
							
							
							
							
							
							
							
							
							$('#objectNumber').ddrInputs('change', function(item) {
								let value = $(item).val(),
									number = parseInt(value),
									slice = String(number).slice(0, 5),
									formatValue = slice.padStart(5, '0');
								$(item).val(formatValue);
							});
							
							
							
							$('#withoutBuyCheck').on('change', function() {
								let isChecked = $(this).is(':checked');
								if (isChecked) {
									$('input[name="buy_number"]').ddrInputs('disable');
									$('input[name="buy_number"]').val('БЕЗ ЗАКУПКИ');
									$('input[name="buy_number"]').ddrInputs('state', 'clear');
									
									$('#dateBuyField').ddrInputs('disable');
									$('#dateBuyField').val('');
									$('#dateBuyField').removeAttrib('date');
									$('#dateBuyFieldhidden').val('');
									
								} else {
									$('input[name="buy_number"]').ddrInputs('enable');
									$('input[name="buy_number"]').val('');
									$('#dateBuyField').ddrInputs('enable');
								}
							});
							
							
							
							
							let isEnabledBtns = false;
							$('#contractForm').ddrInputs('change', function(item) {
								if (!isEnabledBtns) {
									enableButtons(false);
									isEnabledBtns = true;
								}
							});
							
							
							
							
							$('#contractCustomer').on('input', (input) => {
								let customer = $(input.target).val();
								$(input.target).ddrInputs('disable');
								
								let contractFormDepsStepsWait = $('#contractFormDepsSteps').ddrWait();
								
								
								axiosQuery('get', 'ajax/contracts/set_customer_rules', {customer}, 'json').then(({data, error, status, headers}) => {
									$(input.target).ddrInputs('enable');
									
									if (data) {
										$('#contractFormDepsSteps').find('[stepcheck]').ddrInputs('checked', false);
										$('#contractFormDepsSteps').find('[stepassignedselect]').ddrInputs('selected', false);
										$('#contractFormDepsSteps').find('[stepassignedselect], [stepdeadline]').ddrInputs('state', 'clear');
										
										$('#contractFormDepsSteps').find('[stepdeadline]').ddrInputs('disable');
										$('#contractFormDepsSteps').find('[stepassignedselect]').ddrInputs('disable');
										
										$('#contractFormDepsSteps').find('[showindepartment]').ddrInputs('checked', false);
										$('#contractFormDepsSteps').find('[showindepartment]').ddrInputs('disable');
										
										
										$.each(data, (dept, steps) => {
											$.each(steps, (k, step) => {
												$('[stepcheck="'+dept+'|'+step+'"]').ddrInputs('checked');
												$('[stepassignedselect="'+dept+'|'+step+'"]').ddrInputs('enable');
												$('[stepdeadline="'+dept+'|'+step+'"]').ddrInputs('enable');
											});
											
											
											let showindepartment = $('[stepcheck^="'+dept+'|"]').closest('[depblock]').find('[showindepartment]');
											$(showindepartment).ddrInputs('enable');
										});
										
									}
									
									contractFormDepsStepsWait.destroy();
									
								}).catch((e) => {
									console.log(e);
									$(input.target).ddrInputs('enable');
								});
							});
							
						});
					}
				});
				
				
				
				
				
				$.contractUpdate = (_, id) => {
					wait();
					
					$('#genPercentFormField').val($('#genPercent:visible').val() || $('#subPercent:visible').val());
					
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
		
		
		
		
		
		
		
		
		
		
		//------------------------------------------------------------
		
		
		
		let statusesTooltip;
		$.contractStatuses = (btn, id) => {
			statusesTooltip = $(btn).ddrTooltip({
				cls: 'w30rem',
				placement: 'auto',
				tag: 'noscroll',
				minWidth: '360px',
				minHeight: '200px',
				duration: [200, 200],
				trigger: 'click',
				wait: {
					iconHeight: '40px'
				},
				onShow: function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
					query({method: 'get', route: 'get_deps_hidden_statuses', data: {id, views: viewsPath}}, (data, container, {error, status, headers}) => {
						setData(data);
						waitDetroy();
					});
					
					
					
					$.changeDeptHideStatus = (checkbox, deptId) => {
						$(checkbox).ddrInputs('disable');
						let isChecked = $(checkbox).is(':checked');
						query({method: 'put', route: 'set_dept_hidden_status', data: {contract_id: id, department_id: deptId, hide: isChecked}, responseType: 'json'}, (data, container, {error, status, headers}) => {
							console.log(data);
							$(checkbox).ddrInputs('enable');
							
							if (error) {
								$.notify('Не удалось изменить статус договора в отделе', 'error');
								return;
							}
							
							if (data) {
								$.notify('Статус успешно изменен!');
							}
						});
					}
				}
			});
		}
		
		
		
		
		
		
		
		
		
		
		
		
	});
	
	
</script>