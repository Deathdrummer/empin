<section>
	<x-card
		id="contractsCard"
		class="noselect"
		title="Договоры"
		cando="sozdanie-dogovora:site"
		button="Новый договор"
		action="contractNew"
		loading
		>
		
		<div class="row mb2rem gx-30 align-items-center">
			<div class="col-auto">
				<x-chooser variant="neutral" group="normal" px="10">
					<x-chooser.item
						id="chooserAll"
						action="getListAction:0"
						class="pl3rem"
						active
						>Все активные договоры 
							<strong
								class="
									ml5px
									iconed
									iconed-noempty
									border-rounded-8px
									border-all
									border-white
									bg-yellow
									color-dark
									w2rem-2px
									h2rem-2px
									fz13px
									lh100"
								selectionscounts
								></strong></x-chooser.item>
					@if($user->department->id)
						<x-chooser.item
							id="chooserDepartment"
							class="pl3rem"
							action="getListAction:{{$user->department->id}}"
							>{{$user->department->name ?? 'Без названия'}}
							<strong
								class="
									ml5px
									iconed
									iconed-noempty
									border-rounded-8px
									border-all
									border-white
									bg-yellow
									color-dark
									w2rem-2px
									h2rem-2px
									fz13px
									lh100"
								selectionscounts
								></strong></x-chooser.item>
					@endif
					<x-chooser.item
						id="chooserArchive"
						class="pl3rem"
						action="getListAction:-1"
						>Архив
						<strong
							class="
								ml5px
								iconed
								iconed-noempty
								border-rounded-8px
								border-all
								border-white
								bg-yellow
								color-dark
								w2rem-2px
								h2rem-2px
								fz13px
								lh100"
								selectionscounts
								></strong></x-chooser.item>
				</x-chooser>
			</div>
			
			<div class="col-auto">
				<x-input
					id="contractsSearchField"
					group="normal"
					type="search"
					class="w40rem"
					action="contractsSearch"
					icon="magnifying-glass"
					{{-- iconaction="contractsSearch:1" --}}
					iconbg="light"
					placeholder="Поиск..."
					cleared
					tag="tool:56"
					/>
				
				<x-button
					id="clearSearch"
					group="normal"
					variant="red"
					disabled
					action="clearContractsSearch"
					w="3rem"
					title="Очестить поиск"
					><i class="fa-solid fa-xmark"></i></x-button>
			</div>
			
			<div class="col-auto">
				<x-checkbox
					class="mt3px"
					id="searchWithArchive"
					group="normal"
					label="Включить в поиск архив"
					action="searchWithArchive"
					/>
			</div>
			
			<div class="col-auto">
				<x-button
					group="normal"
					variant="neutral"
					action="openSelectionsWin"
					title="Список подборок"
					tag="selectionsbtn"
					>Подборки</x-button>
				
				<x-button
					group="normal"
					w="3rem"
					variant="red"
					action="clearSelection"
					title="Отменить подборку"
					tag="selectionsbtn"
					disabled
					><i class="fa-solid fa-xmark"></i></x-button>
			</div>
			
			<div class="col-auto ms-auto">
				<x-button
					group="normal"
					variant="purple"
					action="openSetColumsWin"
					title="Отображение столбцов"
					><i class="fa-solid fa-table-columns"></i></x-button>
			</div>
		</div>
		
				
				
		<div id="contractsList"></div>
	</x-card>
<div id="toolbar-options" hidden>
   <a href="#"><i class="fa fa-plane"></i></a>
   <a href="#"><i class="fa fa-car"></i></a>
   <a href="#"><i class="fa fa-bicycle"></i></a>
</div>
</section>

<script type="module">
	
	let abortCtrl,
		currentList = 0,
		sortField = ddrStore('site-contracts-sortfield') || 'id',
		sortOrder = ddrStore('site-contracts-sortorder') || 'desc',
		search = null,
		selection = null,
		editSelection = null,
		searchWithArchive = false;
	
	
	getList({init: true});
	
	
	
	$.getListAction = (btn, isActive, list) => {
		if (isActive) return false;
		currentList = list;
		getList();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------- Фильтры
	
	
	
	let searchContractsTOut;
	$.contractsSearch = (btn) => {
		clearTimeout(searchContractsTOut);
		
		searchWithArchive = $('#searchWithArchive').is(':checked');
		
		$('#clearSearch').ddrInputs('enable');
		
		searchContractsTOut = setTimeout(() => {
			search = $(btn).val();
			getList();
		}, 300);
	}
	
	
	$.clearContractsSearch = (btn) => {
		$('#contractsSearchField').val('');
		search = null;
		
		getList();
		$(btn).ddrInputs('disable');
	}
	
	
	$.searchWithArchive = (input) => {
		searchWithArchive = $('#searchWithArchive').is(':checked');
		if ($('#contractsSearchField').val() != '') getList();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------- Подборки
	
	
	$.openSelectionsWin = (openSelectionBtn) => {
		ddrPopup({
			title: 'Подборки договоров',
			width: 800,
			buttons: ['Закрыть', {action: 'selectionAdd', title: 'Создать'}],
			disabledButtons: true,
			winClass: 'ddrpopup_white'
		}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			const viewsPath = 'site.section.contracts.render.selections';
			
			let init = await axiosQuery('get', 'site/selections/init', {views: viewsPath});
			
			if (init.error) {
				console.log(init.error);
				wait(false);
				return false;
			}
			
			setHtml(init.data, () => {
				$('#selectionsList').ddrWait({
					iconHeight: '26px',
					bgColor: '#ffffffd6'
				});
				
				$.ddrCRUD({
					container: '#selectionsList',
					itemToIndex: 'tr',
					route: 'site/selections',
					//params: {
					//	list: {department_id: deptId},
					//	store: {department_id: deptId},
					//},
					viewsPath,
				}).then(({error, list, changeInputs, create, store, storeWithShow, update, destroy, remove, query, getParams}) => {
					wait(false);
					enableButtons(true);
					changeInputs({'[save], [update]': 'enable'});
					
					
					
					$.selectionAdd = (btn) => {
						let selectionAddBtnWait = $(btn).ddrWait({
							iconHeight: '18px',
							bgColor: '#ffffff91'
						});
						
						create((data, container, {error}) => {
							selectionAddBtnWait.destroy();
							if (data) $(container).append(data);
							if (error) $.notify(error.message, 'error');
						});
					}
					
					
					
					$.selectionSave = (btn) => {
						let row = $(btn).closest('tr');
					
						let selectionSaveWait = $(row).ddrWait({
							iconHeight: '26px',
							bgColor: '#ffffffd6'
						});
						
						storeWithShow(row, (data, container, {error}) => {
							if (data) {
								$(row).replaceWith(data);
								$.notify('Запись успешно сохранена!');
							}
							
							if (error) {
								selectionSaveWait.destroy();
								$.notify(error.message, 'error');
							} 
							
							if (error.errors) {
								$.each(error.errors, function(field, errors) {
									$(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
								});
							}
						});
					}
					
					
					
					$.selectionUpdate = (btn, id) => {
						let row = $(btn).closest('tr');
						
						let updateSelectionWait = $(row).ddrWait({
							iconHeight: '15px',
							bgColor: '#ffffff91'
						});
					
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
							
							updateSelectionWait.destroy();
						});
					}
					
					
					
					$.selectionRemove = (btn, id) => {
						let row = $(btn).closest('tr');
						
						if (!id) {
							remove(row);
						} else {
							dialog('Удалить подборку?', {
								buttons: {
									'Удалить|red': function({closeDialog}) {
										closeDialog();
										
										let removeSelectionWait = $(row).ddrWait({
											iconHeight: '15px',
											bgColor: '#ffffff91'
										});
										
										destroy(id, function(stat) {
											if (stat) {
												remove(row);
												$.notify('Запись успешно удалена!');
											} else {
												$.notify('Ошибка удаления записи!', 'error');
											} 
											
											removeSelectionWait.destroy();
										});
									},
									'Отмена|light': function({closeDialog}) {
										closeDialog();
									}
								}
							});
						}
					}
					
					
					
					
					
					
					
					
					
					//--------------------------------- Действия со списками
					
					
					$.selectionBuildList = (btn, id) => {
						$('[selectionsbtn]').ddrInputs('disable');
						close();
						selection = id;
						editSelection = null;
						getList({
							withCounts: true,
							callback: function() {
								$('[selectionsbtn]').ddrInputs('enable');
							}
						});
					}
					
					
					
					
					
					
					$.selectionBuildToEdit = (btn, id) => {
						$('[selectionsbtn]').ddrInputs('disable');
						close();
						selection = id;
						editSelection = true;
						getList({
							withCounts: true,
							callback: function() {
								$('[selectionsbtn]').ddrInputs('enable');
							}
						});
					}
					
					
				});
			});
		});
	}
	
	
	
	
	
	
	
	$.addContractToSelection = (select, contractId) => {
		let selectionId = parseInt($(select).val());
		
		$(select).ddrInputs('disable');
		axiosQuery('put', 'site/selections/add_contract', {contractId, selectionId}).then(({data, error, status, headers}) => {
			if (error) {
				$.notify('Ошибка добавления в подборку!', 'error');
				console.log(error?.message, error.errors);
				$(select).ddrInputs('error');
			} else {
				$(select).find('option[value="'+selectionId+'"]').setAttrib('disabled');
				$(select).ddrInputs('enable');
				$.notify('Договор успешно добавлен в подборку!');
			}
		});
	}
	
	
	
	
	
	
	
	$.removeContractFromSelection = (btn, contractId, selectionId) => {
		$(btn).ddrInputs('disable');
		$('[selectionsbtn]').ddrInputs('disable');
		
		axiosQuery('put', 'site/selections/remove_contract', {contractId, selectionId}).then(({data, error, status, headers}) => {
			if (error) {
				$.notify('Ошибка удаления из подборки!', 'error');
				console.log(error?.message, error.errors);
			} else {
				$.notify('Договор успешно удален из подборки!');
				getList({
					withCounts: true,
					callback: function() {
						$('[selectionsbtn]').ddrInputs('enable');
					}
				});
			}
		});
	}
	
	
	
	
	
	
	$.clearSelection = (btn) => {
		selection = null;
		editSelection = null;
		
		getList({
			callback: function() {
				$(btn).ddrInputs('disable');
				$('#chooserAll').find('[selectionscounts]').empty();
				$('#chooserDepartment').find('[selectionscounts]').empty();
				$('#chooserArchive').find('[selectionscounts]').empty();
			}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------  сортировка
	
	$.sorting = (cell, sfield) => {
		$(cell).closest('[sorts]').find('.sort-asc, .sort-desc').removeClass('sort-asc sort-desc');
		if (sortField == sfield) {
			sortOrder = sortOrder == 'asc' ? 'desc' : 'asc';
			ddrStore('site-contracts-sortorder', sortOrder);
			$(cell).removeClass('sort-'+(sortOrder == 'asc' ? 'desc' : 'asc')).addClass('sort-'+sortOrder);
		} else {
			sortField = sfield;
			sortOrder = 'asc';
			
			ddrStore('site-contracts-sortorder', sortOrder);
			ddrStore('site-contracts-sortfield', sortField);
			
			$(cell).addClass('sort-'+sortOrder);
		}
		
		getList();
	}
	
	
	
	
	
	
	
	
	
	//------------------------------------------------- Отображение столбцов
	$.openSetColumsWin = (btn) => {
		ddrPopup({
			title: 'Отображение столбцов',
			width: 500,
			buttons: ['Закрыть', {action: 'setContractsColums', title: 'Прменить'}],
			winClass: 'ddrpopup_white'
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			axiosQuery('get', 'site/contracts/colums').then(({data, error, status, headers}) => {
				
				if (error) {
					$.notify('Ошибка удаления из подборки!', 'error');
					console.log(error?.message, error?.errors);
				} 
				
				setHtml(data);
				wait(false);
			});
			
			$.setContractsColums = (_) => {
				wait();
				let checkedColums = [];
				$('#contractColumnList').find('[contractcolumn]:checked').each(function(k, item) {
					checkedColums.push($(item).attr('contractcolumn'));
				});
				
				axiosQuery('put', 'site/contracts/colums', {checkedColums}).then(({data, error, status, headers}) => {
					if (error) {
						$.notify('Ошибка удаления из подборки!', 'error');
						console.log(error?.message, error.errors);
						wait(false);
					} else {
						$.notify('Договор успешно удален из подборки!');
						getList({
							callback: function() {
								//$('[selectionsbtn]').ddrInputs('enable');
							}
						});
						close();
					}
					
				});
			}
			
		});
	}
	
	
	
			
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------- Убрать отметку нового договора
	$.checkNewContract = (tr, contractId) => {
		axiosQuery('put', 'site/contracts/check_new', {contract_id: contractId}).then(({data, error, status, headers}) => {
			if (!data) {
				$.notify('Запрещено!', 'error');
				return;
			}
			
			$(tr).removeClass('clear bg-yellow-light');
		}).catch((e) => {
			console.log(e);
		});
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------  Указание цвета статуса
	let statusesTooltip, contractId;
	$.openColorsStatuses = (btn, cId) => {
		contractId = cId;
		statusesTooltip = $(btn).tooltip({
			cls: 'w30rem',
			placement: 'auto',
			tag: 'noscroll',
			minWidth: '320px',
			minHeight: '110px',
			wait: {
				iconHeight: '40px'
			},
			onShow: function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
				loadStatusesData((data) => {
					setData(data);
					waitDetroy();
				});
			}
		});
	}
	
	let statusesData;
	function loadStatusesData(callback = false) {
		if (!callback) return;
		/*if (statusesData) {
			callback(statusesData);
			return;
		} */
		
		axiosQuery('get', 'site/contracts/statuses', {contract_id: contractId}).then(({data, error, status, headers}) => {
			if (!data) {
				$.notify('Запрещено!', 'error');
				statusesTooltip.destroy();
				return;
			}
			statusesData = data;
			callback(statusesData);
		}).catch((e) => {
			console.log(e);
		});
	}
	
	
	
	
	
	
	$.setColorStatus = (key, color, name) => {
		statusesTooltip.wait();
		axiosQuery('put', 'site/contracts/set_status', {contractId, key}).then(({data, error, status, headers}) => {
			let dColor = $(statusesTooltip.reference).attr('dcolor'),
				dName = $(statusesTooltip.reference).attr('dname');
			
			if (color) {
				$(statusesTooltip.reference).css('background-color', color);
				$(statusesTooltip.reference).attr('title', name);
				
				$(statusesTooltip.reference).removeClass('border-gray-300');
				$(statusesTooltip.reference).addClass('border-blue border-width-2px');
			} else {
				$(statusesTooltip.reference).css('background-color', dColor);
				$(statusesTooltip.reference).attr('title', dName);
				
				$(statusesTooltip.reference).removeClass('border-blue border-width-2px');
				$(statusesTooltip.reference).addClass('border-gray-300');
			} 
			
			statusesTooltip.destroy();
		}).catch((e) => {
			console.log(e);
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	// Заполнение данных договоров
	let contractSetDataTOut, oldInputId = null;
	$.contractSetData = (input, contractId, departmentId, stepId, type) => {
		$(input).ddrInputs('addClass', 'notouch');
		let inputId = $(input).attr('id');
		if (oldInputId == inputId) clearTimeout(contractSetDataTOut);
		oldInputId = inputId;
		
		
		contractSetDataTOut = setTimeout(function() {
			let value;
			switch(type) {
				case 1: //  чекбокс
					value = $(input).is(':checked') ? 1 : 0;
			    	break;
			    
			    case 2: //  текст
					value = $(input).val();
			    	break;
			    
			    case 3: // вып. список
					value = parseInt($(input).val());
			    	break;
			    
			    case 4: // сумма.
					value = parseFloat($(input).val());
			    	break;

				default:
					break;
			}
			
			axiosQuery('put', 'site/contracts', {contractId, departmentId, stepId, type, value}).then(({data, error, status, headers}) => {
				if (error) {
					$.notify('Ошибка сохранения данных!', 'error');
					console.log(error?.message, error.errors);
				}
				$(input).ddrInputs('removeClass', 'notouch');
				oldInputId = null;
			});
		}, 300);
	}
	
	
	
	
	
	
	// создать договор
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
			
			axiosQuery('get', 'ajax/contracts/create', {views: 'admin.section.contracts.render.contracts', newItemIndex: 0})
				.then(({data, error, status, headers}) => {
					if (error) {
						$.notify(error.message, 'error');
						console.log(error?.message, error.errors);
					}
					
					if (data) setHtml(data, () => {
						enableButtons('close');
						$('input[name="price"]').number(true, 2, '.', ' ');
						$('#contractForm').ddrInputs('change', function(item) {
							enableButtons(false);
						});
					});
				});
			
			
			
			
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
			
			
			
			
			
			$.contractStore = (btn) => {
				wait();
				//let form = new FormData(document.querySelector('#newContractForm'));
				let formSelector = $('#contractForm');
				let form = $(formSelector).ddrForm(formSelector);
				
				axiosQuery('post', 'ajax/contracts', form, 'text').then(({data, error, status, headers}) => {
					if (data) {
						$.notify('договор успешно создан!');
						if (currentList != -1) getList();
						close();
					}
					
					if (error) {
						$.notify(error.message, 'error');
						wait(false);
					} 
					
					if (error.errors) {
						console.log(error.errors);
						$.each(error.errors, function(field, errors) {
							$(formSelector).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
						});
					}
				}).catch((e) => {
					console.log(e);
				});
				
			}
		});	
	}
	
	
	
	
	
	
	// Скрыть договор
	$.hideContractAction = (btn, contractId, departmentId) => {
		let row = $(btn).closest('tr');
		ddrPopup({
			width: 400, // ширина окна
			html: '<p class="fz18px color-red">Вы действительно хотите скрыть договор?</p>', // контент
			buttons: ['ui.cancel', {title: 'Скрыть', variant: 'red', action: 'contractHide'}],
			centerMode: true,
			winClass: 'ddrpopup_dialog'
		}).then(({close, wait}) => {
			$.contractHide = (_) => {
				wait();
				axiosQuery('post', 'site/contracts/hide', {contractId, departmentId}, 'json').then(({data, error, status, headers}) => {
					if (data) {
						getList();
						$.notify('Договор успешно скрыт!');
					} else {
						$.notify('Ошибка! Договор не был скрыт!', 'error');
					}
					close();
				});
			}
		});	
	}
	
	
	
	
	
	// Отправить договор в архив
	$.toArchiveContractAction = (btn, contractId) => {
		let row = $(btn).closest('tr');
		ddrPopup({
			width: 400, // ширина окна
			html: '<p class="fz18px color-red">Вы действительно хотите отправить договор в архив?</p>', // контент
			buttons: ['ui.cancel', {title: 'Отправить', variant: 'red', action: 'contractToArchiveAction'}],
			centerMode: true,
			winClass: 'ddrpopup_dialog'
		}).then(({close, wait}) => {
			$.contractToArchiveAction = (_) => {
				wait();
				axiosQuery('post', 'site/contracts/to_archive', {contractId}, 'json').then(({data, error, status, headers}) => {
					if (data) {
						getList();
						$.notify('Договор успешно отправлен в архив!');
					} else {
						$.notify('Ошибка! Договор не был отправлен в архив!', 'error');
					}
					close();
				});
			}
		});
	}
	
	
	
	
	
	
	
	
	// Отправить договор в другой отдел
	$.sendContractAction = (rowBtn, contractId) => {
		ddrPopup({
			title: 'Отправить договор в отдел',
			width: 500, // ширина окна
			url: 'site/contracts/departments?contract_id='+contractId,
			method: 'get',
			buttons: ['ui.cancel']
		}).then(({close, wait}) => {
			$.sendToDepartment = (btn, departmentId) => {
				let rowsCount = $(btn).closest('[departmentslist]').find('[departmentitem]').length,
					departmentName = $(btn).text().trim();
				wait();
				axiosQuery('post', 'site/contracts/send', {contractId, departmentId}, 'json').then(({data, error, status, headers}) => {
					if (data) {
						$.notify('Договор успешно отправлен в '+departmentName+'!');
						if (rowsCount == 1) $(rowBtn).ddrInputs('disable');
					} else {
						$.notify('Ошибка! Договор не был отправлен!', 'error');
					}
					close();
				});
			}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-----------------------------------------------------------------------------------------------------
	
	
	function getList(settings) {
		if (abortCtrl instanceof AbortController) abortCtrl.abort();
		
		let {
			init,
			withCounts,
			callback
		} = _.assign({
			init: false,
			withCounts: false,
			callback: false
		}, settings),
			params = {},
			listWait;
		
		if (currentList == -1 || currentList > 0) {
			searchWithArchive = false;
			$('#searchWithArchive').ddrInputs('disable');
			$('#searchWithArchive').ddrInputs('checked', false);
		} else {
			$('#searchWithArchive').ddrInputs('enable');
		}
		
		
		//-----------------------------------
		
		if (currentList > 0) {
			params['archive'] = 0;
			params['department_id'] = currentList;
			params['pivot'] = {
				department_id: currentList,
				show: 1,
				hide: 0
			};
		} else {
			params['archive'] = currentList == -1 ? 1 : (searchWithArchive ? null : 0);
		}
		
		params['sort_field'] = sortField;
		params['sort_order'] = sortOrder;
		params['search'] = search;
		params['selection'] = selection;
		params['edit_selection'] = editSelection;
		
		
		
		//-----------------------------------
		
		if (!init) {
			listWait = $('#contractsList').ddrWait({
				iconHeight: '40px',
				text: 'Загрузка',
				fontSize: '14px',
				bgColor: '#ffffffbb'
			});
		} else {
			
		}
		
		abortCtrl = new AbortController();
		axiosQuery('get', 'site/contracts', params, 'text', abortCtrl).then(({data, error, status, headers}) => {
			$('#contractsList').html(data);
			if (init) $('#contractsCard').card('ready');
			else listWait.destroy();
			
			$('[stepprice]').number(true, 2, '.', ' ');
			
			if (withCounts && headers) {
				$('#chooserAll').find('[selectionscounts]').text(headers['x-count-contracts-all'] > 0 ? headers['x-count-contracts-all'] : '');
				$('#chooserDepartment').find('[selectionscounts]').text(headers['x-count-contracts-department'] > 0 ? headers['x-count-contracts-department'] : '');
				$('#chooserArchive').find('[selectionscounts]').text(headers['x-count-contracts-archive'] > 0 ? headers['x-count-contracts-archive'] : '');
			}
			
			if (callback && typeof callback == 'function') callback();
		});
		
	}
	
	
	
	
	
	
</script>