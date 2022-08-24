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
				<x-chooser variant="neutral" group="normal" px="20">
					<x-chooser.item action="getListAction:0" active>Все активные договоры</x-chooser.item>
					@if($user->department->id)
						<x-chooser.item action="getListAction:{{$user->department->id}}">{{$user->department->name ?? 'Без названия'}}</x-chooser.item>
					@endif
					<x-chooser.item action="getListAction:-1">Архив</x-chooser.item>
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
					class="w4rem ml5px"
					title="Очестить поиск"
					><i class="fa-solid fa-xmark"></i></x-button>
			</div>
			
			<div class="col-auto">
				<x-checkbox
					class="mt3px"
					id="searchWithArchive"
					group="large"
					label="Включить в поиск архив"
					action="searchWithArchive"
					/>
			</div>
			
			<div class="col-auto">
				<x-buttons-group group="normal">
					<x-button
						variant="neutral"
						action=""
						title="Список подборок"
						>подборки</x-button>
					
					<x-button
						variant="neutral"
						action=""
						title="Добавить выбранные в подборку"
						><i class="fa-solid fa-plus"></i></x-button>
				</x-buttons-group>
					
				
			</div>
		</div>
		
				
				
		<div id="contractsList"></div>
	</x-card>

</section>

<script type="module">
	
	let currentList = 0,
		sortField = ddrStore('site-contracts-sortfield') || 'id',
		sortOrder = ddrStore('site-contracts-sortorder') || 'desc',
		search = null,
		searchWithArchive = false,
		selectedContracts = [];
	
	getList(true);
	
	
	
	$.getListAction = (btn, isActive, list) => {
		if (isActive) return false;
		currentList = list;
		getList();
	}
	
	
	
	
	$('#contractsList').find('[addcontracttoselection]').each((item) => {
		let id = $(item).attr('[addcontracttoselection]');
		console.log(id);
	});
	
	
	
	
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
		
		selectedContracts = [];
		
		getList();
		$(btn).ddrInputs('disable');
	}
	
	
	$.searchWithArchive = (input) => {
		searchWithArchive = $('#searchWithArchive').is(':checked');
		if ($('#contractsSearchField').val() != '') getList();
	}
	
	
	
	$.addContractToSelection = (input, id) => {
		if ($(input).is(':checked')) {
			selectedContracts.push(id);
		} else {
			_.pull(selectedContracts, id);
		}
	}
	
	
	
	
	
	
	
	
	// сортировка
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
	
	
	function getList(init) {
		let params = {}, listWait;
		
		
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
		params['selected_contracts'] = selectedContracts;
		
		
		
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
		
		axiosQuery('get', 'site/contracts', params).then(({data, error, status, headers}) => {
			$('#contractsList').html(data);
			if (init) $('#contractsCard').card('ready');
			else listWait.destroy();
			
			$('[stepprice]').number(true, 2, '.', ' ');
		});
	}
	
	
	
	
	
	
</script>