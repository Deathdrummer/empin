<section>
	<x-card
		id="contractsCard"
		class="noselect"
		loading
		>
		
		
		<li
			onclick="$.commonSettings()"
			teleport="#menuTeleport"
			><span>Настройки</span>
		</li>
		<li
			onclick="$.openSetColumsWin()"
			teleport="#menuTeleport"
			><span>Настроить отображение столбцов</span>
		</li>
		<li
			onclick="$.openSetDepsWin()"
			teleport="#menuTeleport"
			><span>Настроить отображение отделов</span>
		</li>
		
		
		{{-- <x-button
					group="normal"
					variant="purple"
					action="openSetColumsWin"
					title="Отображение столбцов"
					teleport="#headerTeleport"
					><i class="fa-solid fa-table-columns"></i></x-button> --}}
		
		
		<div class="row gx-30 align-items-center" teleport="#headerTeleport">
			<div class="col-auto">
				<x-input
					style="box-shadow: 0 0 8px 0 #0000000a;"
					id="contractsSearchField"
					group="large"
					type="search"
					class="w40rem"
					action="contractsSearch"
					icon="magnifying-glass"
					{{-- iconaction="contractsSearch" --}}
					{{-- iconbg="light" --}}
					placeholder="Поиск..."
					cleared
					tag="tool:56"
					/>
				
				{{-- <x-button
					id="clearSearch"
					group="large"
					variant="red"
					disabled
					action="clearContractsSearch"
					w="3rem"
					title="Очестить поиск"
					><i class="fa-solid fa-xmark"></i></x-button> --}}
			</div>
			
			{{-- <div class="col-auto">
				<x-checkbox
					class="mt3px"
					id="searchWithArchive"
					group="large"
					label="Включить в поиск архив"
					action="searchWithArchive"
					/>
			</div> --}}
			
			<div class="col-auto">
				<x-button
					style="border-radius: 10px; border-color:transparent;"
					group="large"
					variant="light"
					action="openSelectionsWin"
					title="Список подборок"
					px="20"
					tag="selectionsbtn"
					>Подборки</x-button>
				
			
			{{-- <div class="col-auto ms-auto">
				<x-button
					group="normal"
					variant="purple"
					action="openSetColumsWin"
					title="Отображение столбцов"
					><i class="fa-solid fa-table-columns"></i></x-button>
			</div> --}}
			

				@cando('sozdanie-dogovora:site')
					<x-button
						style="border-radius: 10px; border-color:transparent; margin-left:20px;"
						group="large"
						variant="light"
						action="contractNew"
						px="20"
						>Новый договор <i class="fa-solid fa-plus"></i></x-button>
				@endcando
			</div>
		</div>
		
		<div class="row mb2rem" id="tableContainer">
			<div class="col">
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
									w2rem-8px
									h2rem-2px
									fz12px
									lh100"
								selectionscounts
								></strong></x-chooser.item>
					
					@if(auth('site')->user()->can('can-see-all-departments:site'))
						@if(!is_null($departments) && !empty($departments))
							@foreach($departments as $dep)
								<x-chooser.item
									chooserdepartment="{{$dep->id}}"
									class="pl3rem"
									tag="chooserd epartment"
									action="getListAction:{{$dep->id}}"
									>{{$dep->name ?? 'Без названия'}}
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
											w2rem-8px
											h2rem-2px
											fz12px
											lh100"
										selectionscounts
										></strong></x-chooser.item>
							@endforeach
						@endif
					@elseif(isset($user->department->id) && !is_null($user->department->id))
					<x-chooser.item
							chooserdepartment="{{$user->department->id}}"
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
									w2rem-8px
									h2rem-2px
									fz12px
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
								w2rem-8px
								h2rem-2px
								fz13px
								lh100"
								selectionscounts
								></strong></x-chooser.item>
				</x-chooser>
			</div>
		</div>
		
		
		<div class="currentselection">
			<div class="currentselection__block" id="currentSelection" hidden>
				<div class="currentselection__label">
					<p id="currentSelectionTitle"></p>
				</div>
				
				<x-button
					style="border-radius: 5px; border-color:transparent;height:26px;"
					class="ml5px"
					group="verysmall"
					w="2rem-6px"
					variant="red"
					action="clearSelection"
					title="Отменить подборку"
					tag="selectionsbtn"
					disabled
					><i class="fa-solid fa-xmark"></i></x-button>
			</div>
		</div>
			
		<div id="contractsTable"></div>
		
	</x-card>
</section>





<script type="module">
	let abortCtrl,
		abortCtrlCounts,
		abortCtrlFilter,
		abortCtrlFilterDates,
		currentList = 0,
		sortField = ddrStore('site-contracts-sortfield') || 'id',
		sortOrder = ddrStore('site-contracts-sortorder') || 'desc',
		limit = {{$setting['contracts-per-page'] ?? 10}},
		priceNds = {{$setting['price-nds'] ?? 1}},
		countShownLoadings = {{$setting['count-shown-loadings'] ?? 2}},
		canCreateCheckbox = '{{Auth::guard('site')->user()->can('contract-create-checkbox::site')}}',
		canRemoveCheckbox = '{{Auth::guard('site')->user()->can('contract-remove-checkbox::site')}}',
		offset = 0,
		search = null,
		columnFilter = null, // поиск по значению из сстолбца
		selection = null,
		editSelection = null,
		searchWithArchive = false,
		selectedContracts = {},
		loadedContractsIds = {},
		lastChoosedRow = null,
		totalCount = null;
		
	
	//--------------------------------------------------------------------------------- Расширения
	
	// виртуальный список подгружаемых ID договоров
	$.extend(loadedContractsIds, {
		parts: {},
		add(contractIds) {
			if (loadedContractsIds.parts[offset] === undefined && !_.isEmpty(contractIds)) {
				loadedContractsIds.parts[offset] = JSON.parse(contractIds);
			}
		},
		get(fromId = null, toId = null) {
			const allItems = _.union(...Object.values(loadedContractsIds.parts)) || false;
			if (!fromId && !toId) return allItems;
			if (!fromId || !toId) return false;
					
			let fromIndex = allItems.indexOf(parseInt(fromId)),
				toIndex = allItems.indexOf(parseInt(toId));
			
			if (fromIndex == -1 || toIndex == -1) return false;
			
			let startIndex = Math.min(fromIndex, toIndex),
				endIndex = Math.max(fromIndex, toIndex);
			
			return allItems.slice(startIndex, endIndex + 1);
		},
		clear() {
			loadedContractsIds.parts = {};
		}
	});
	
	
	// Список ID выделенных договоров
	$.extend(selectedContracts, {
		items: [],
		add(items = null) {
			if (_.isNull(items)) return;
			items = !_.isArray(items) ? [parseInt(items)] : items;
			selectedContracts.items = items;
		},
		remove(item = null) {
			if (_.isNull(item)) return;
			let removeIndex = selectedContracts.items.indexOf(parseInt(item));
			if (removeIndex == -1) return;
			selectedContracts.items.splice(removeIndex, 1);
		},
		append(item = null) {
			if (_.isNull(item)) return;
			selectedContracts.items.push(parseInt(item));
		},
		clear() {
			selectedContracts.items = [];
		}
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	getList({init: true});
	
	
	
	$.getListAction = (btn, isActive, list) => {
		if (isActive) return false;
		currentList = list;
		getList();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Фильтры
	
	let searchContractsTOut;
	$.contractsSearch = (field) => {
		clearTimeout(searchContractsTOut);
		
		searchWithArchive = $('#searchWithArchive').is(':checked');
		
		//$('#clearSearch').ddrInputs('enable');
		
		searchContractsTOut = setTimeout(() => {
			search = $(field).val();
			_clearCounts();
			if (search == '') {
				search = null;
				let replaceIconHtml = '<div class="postfix_icon bg-light"><i class="fa-solid fa-magnifying-glass"></i></div>';	
				$('#contractsSearchField').parent('.input').find('.postfix_icon').replaceWith(replaceIconHtml);
				getList({
					withCounts: search || selection,
					callback: function() {
						//$('#clearSearch').ddrInputs('disable');
					}
				});
			} else {
				//$('#contractsSearchField').parent('.input').ddrInputs('disable');
				
				getList({
					withCounts: true,
					callback: function() {
						let replaceIconHtml = '<div class="postfix_icon bg-light bg-light-hovered pointer" onclick="$.clearContractsSearch(this)"><i class="fa-solid fa-xmark"></i></div>';
						$('#contractsSearchField').parent('.input').find('.postfix_icon').replaceWith(replaceIconHtml);
						//$('#contractsSearchField').parent('.input').ddrInputs('enable');
					}
				});
			}
		}, 300);
	}
	
	
	$.clearContractsSearch = (btn) => {
		$('#contractsSearchField').val('');
		//$('#contractsSearchField').parent('.input').ddrInputs('disable');
		
		let replaceIconHtml = '<div class="postfix_icon bg-light"><i class="fa-solid fa-magnifying-glass"></i></div>';	
		$('#contractsSearchField').parent('.input').find('.postfix_icon').replaceWith(replaceIconHtml);
		
		search = null;
		_clearCounts();
		getList({
			withCounts: search || selection,
			callback: function() {
				//$('#contractsSearchField').parent('.input').ddrInputs('enable');
			}
		});
		//$(btn).ddrInputs('disable');
	}
	
	
	$.searchWithArchive = (input) => {
		searchWithArchive = $('#searchWithArchive').is(':checked');
		if ($('#contractsSearchField').val() != '') getList();
	}
	
	
	function _clearCounts() {
		$('#chooserAll').find('[selectionscounts]').empty();
		$('[chooserdepartment]').find('[selectionscounts]').empty();
		$('#chooserArchive').find('[selectionscounts]').empty();
	}
	
	
	
	
	
	
	
	
	
	
		
	
	
	
	//--------------------------------------------------------------------------------- Подборки
	
	$.openSelectionsWin = (openSelectionBtn) => {
		ddrPopup({
			title: 'Подборки договоров',
			width: 1000,
			buttons: ['Закрыть', {action: 'selectionAdd', title: 'Создать'}],
			disabledButtons: true,
			winClass: 'ddrpopup_white'
		}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, query, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
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
					
					
					
					$.selectionUnsubscribe = (btn, id) => {
						if (!id) {
							$.notify('Ошибка! Не удалось отписаться!', 'error');
							return;
						}
						let row = $(btn).closest('tr');
						
						dialog('Отписаться от подборки?', {
							buttons: {
								'Отписаться|red': function({closeDialog}) {
									closeDialog();
									
									let unsubscribeSelectionWait = $(row).ddrWait({
										iconHeight: '15px',
										bgColor: '#ffffff91'
									});
									
									query({
										method: 'post',
										route: 'unsubscribe',
										data: {id}, // это ID записи
										responseType: 'json'
									}, (data, container, {error, status, headers}) => {
										if (!error) {
											remove(row);
											$.notify('Отписка успешно выполнена!');
										} else {
											$.notify('Ошибка отписки от подборки!', 'error');
										}
										unsubscribeSelectionWait.destroy();
									});
								},
								'Отмена|light': function({closeDialog}) {
									closeDialog();
								}
							}
						});
					}
					
					
					
					
					
					//--------------------------------- Отправить сообщение в чаты с договорами данной подборки
					$.selectionSendMessages = (sendMessDialogBtn, selectionId) => {
						$(sendMessDialogBtn).ddrInputs('disable');
						
						let sendMessAbortCtrl = new AbortController();
						
						let html = '<strong class="d-block mb5px">Сообщение:</strong>\
									<div class="textarea normal-textarea w40rem" id="sendMessDialogField">\
										<textarea name="" rows="5" class="w100"></textarea>\
									</div>'
						
						dialog(html, {
							buttons: {
								'Отправить': ({closeDialog}) => {
									let sendMessDialogWait = $('#sendMessDialogField').ddrWait();
									$('#ddrpopupDialogBtn0').ddrInputs('disable');
									
									let message = $('#sendMessDialogField').find('textarea').val();
									
									axiosQuery('put', 'site/contracts/chats', {selectionId, message}, 'json', sendMessAbortCtrl)
										.then(({data, error, status, headers}) => {
											if (error) {
												$.notify('Ошибка отправки сообщений!', 'error');
												return;
											}
											
											if (data) {
												if (data == -1) {
													$.notify('Подборка не содержит в себе ни одного договора! Сообщение не было разослано!', 'info');
												} else {
													$.notify('Сообщение успешно отправлено во все чаты договоров!');
												}
												
												closeDialog();
												$(sendMessDialogBtn).ddrInputs('enable');
											
											} else {
												$.notify('Не удалось отправить сообщение в чаты договоров!', 'error');
												sendMessDialogWait.destroy();
											}
											
										}).catch((e) => {
											console.log(e);
										});
								},
								'Отмена|red': ({closeDialog}) => {
									$(sendMessDialogBtn).ddrInputs('enable');
									closeDialog();
									sendMessAbortCtrl.abort();
								} 
							},
							callback() {
								$('#ddrpopupDialogBtn0').ddrInputs('disable');
								
								let isEmpty = true;	
								$('#sendMessDialogField').ddrInputs('change', (textarea) => {
									if ($(textarea).val() && isEmpty) {
										$('#ddrpopupDialogBtn0').ddrInputs('enable');
										isEmpty = false;
									} else if (!$(textarea).val() && !isEmpty) {
										$('#ddrpopupDialogBtn0').ddrInputs('disable');
										isEmpty = true;
									}
								});
							}
						});
						
					}
					
					
					
					
					
					
					//--------------------------------- Поделиться подборкой с другими сотрудниками
					let statusesTooltip, destroyTooltip, sharePopper;
					$.selectionShare = (btn, selection_id, subscribed = false) => {
						
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
								sharePopper = popper;
								destroyTooltip = destroy;
								
								query({method: 'get', route: 'users_to_share', data: {views: viewsPath, selection_id, subscribed}, responseType: 'text'}, (data, container, {error, status, headers}) => {
									if (error) {
										$.notify(error?.message, 'error');
										console.log(error?.errors);
									}
									setData(data);
									waitDetroy();
								});
							}
						});
						
						// clone-user subscribe-user clone-user-department subscribe-user-department
						// тип ID подборки ID отдела или участника
						$.shareSelection = (type, selectionId, unitId, permission) => {
							//destroyTooltip();
							
							let shareSelectionWait = $(sharePopper).ddrWait({
								iconHeight: '25px'
							});
							
							query({
								method: 'post',
								route: 'share',
								data: {
									type,
									unitId,
									selectionId,
									permission
								}
							}, (data, container, {error, status, headers}) => {
								if (error) {
									$.notify('Ошибка отправки подборки!', 'error');
									shareSelectionWait.destroy();
									return;
								}
								
								if (['clone-user', 'clone-user-department'].indexOf(type) !== -1) {
									$.notify('Подборка успешно отправлена!');
								} else {
									$.notify('Подписка успешно оформлена!');
								}
								
								shareSelectionWait.destroy();
							});
						}
						
					}
					
					
					
					
					
					
					//--------------------------------- Действия со списками
					
					$.selectionBuildList = (btn, id) => {
						$('[selectionsbtn]').ddrInputs('disable');
						close();
						selection = id;
						editSelection = null;
						
						let selectionTitle = $(btn).closest('tr').find('input[name="title"]').val();
						
						_clearCounts();
						getList({
							withCounts: true,
							callback: function() {
								$('#currentSelectionTitle').text(selectionTitle);
								$('[selectionsbtn]').ddrInputs('enable');
								$('#currentSelection').removeAttrib('hidden');
							}
						});
					}
					
					
					
					$.selectionBuildToEdit = (btn, id) => {
						$('[selectionsbtn]').ddrInputs('disable');
						close();
						selection = id;
						editSelection = true;
						
						let selectionTitle = $(btn).closest('tr').find('input[name="title"]').val();
						
						_clearCounts();
						getList({
							withCounts: true,
							callback: function() {
								$('#currentSelectionTitle').text(selectionTitle);
								$('[selectionsbtn]').ddrInputs('enable');
								$('#currentSelection').removeAttrib('hidden');
							}
						});
					}
				});
			});
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- отменить текущую подборку
	$.clearSelection = (btn) => {
		$('[selectionsbtn]').ddrInputs('disable');
		selection = null;
		editSelection = null;
		_clearCounts();
		getList({
			withCounts: search || selection,
			callback: function() {
				$('#currentSelection').setAttrib('hidden');
				$('[selectionsbtn]').ddrInputs('enable');
				$('#currentSelectionTitle').text('');
			}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------------------------  сортировка
	
	$.sorting = (cell, sfield) => {
		$('#contractsTable').find('.sort-asc, .sort-desc').removeClass('sort-asc sort-desc');
		
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
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Отображение столбцов
	$.openSetColumsWin = (btn) => {
		ddrPopup({
			title: 'Отображение столбцов',
			width: 500,
			buttons: ['Закрыть', {action: 'setContractsColums', title: 'Применить'}],
			winClass: 'ddrpopup_white'
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			axiosQuery('get', 'site/contracts/colums').then(({data, error, status, headers}) => {
				
				if (error) {
					$.notify('Ошибка удаления из подборки!', 'error');
					console.log(error?.message, error?.errors);
				} 
				
				setHtml(data, {}, () => {
					$("#contractColumnList").sortable({
						axis: 'y',
						placeholder: 'sortable-placeholder h4rem',
						classes: {
							'ui-sortable-placeholder': 'ddrtable__tr',
						},
						/*async stop(event, ui) {
							const sortedData = {};
							$("#contractColumnList").find('[contractcolumn]:checked').each((k, item) => {
								let field = $(item).attr('contractcolumn');
								sortedData[k] = field;
								
							});
						},*/
						cancel: "[nohandle]"
						//handle: ".handle"
					});
					
					/*$('#contractColumnList').sortable({
						animation: 150,
						invertSwap: true,
					});*/
					//var sortable = new Sortable($('#contractColumnList')[0]);
				});
				
				
				wait(false);
			});
			
			
			$.setContractsColums = async (_) => {
				wait();
				const sortableCheckedColums = {};
				$('#contractColumnList').find('[contractcolumn]:checked').each(function(k, item) {
					let idx = parseInt($(item).closest('[ddrtabletr]').index());
					sortableCheckedColums[idx + 1] = $(item).attr('contractcolumn');
				});
				
				const {data, error, status, headers} = await axiosQuery('put', 'site/contracts/colums', {sortableCheckedColums});
				
				if (error) {
					$.notify('Ошибка установки столбцов!', 'error');
					console.log(error?.message, error.errors);
					wait(false);
				} else {
					$.notify('Столбцы успешно заданы!');
					
					getList({
						callback: function() {
							//$('[selectionsbtn]').ddrInputs('enable');
						}
					});
					close();
				}
			}
			
		});
	}
	
	
	
			
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Отображение отделов
	$.openSetDepsWin = (btn) => {
		ddrPopup({
			title: 'Отображение отделов',
			width: 500,
			buttons: ['Закрыть', {action: 'setDepsColums', title: 'Применить'}],
			winClass: 'ddrpopup_white'
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			axiosQuery('get', 'site/contracts/sortdeps').then(({data, error, status, headers}) => {
				
				if (error) {
					$.notify('Ошибка удаления из подборки!', 'error');
					console.log(error?.message, error?.errors);
				} 
				
				setHtml(data, {}, () => {
					$("#contractDepsList").sortable({
						axis: 'y',
						placeholder: 'sortable-placeholder h4rem',
						classes: {
							'ui-sortable-placeholder': 'ddrtable__tr',
						},
						/*async stop(event, ui) {
							const sortedData = [];
							$("#contractDepsList").find('[sortdept]').each((k, item) => {
								let id = $(item).attr('sortdept');
								sortedData.push(id);
								
							});
						},*/
						cancel: "[nohandle]"
						//handle: ".handle"
					});
					
					/*$('#contractDepsList').sortable({
						animation: 150,
						invertSwap: true,
					});*/
					//var sortable = new Sortable($('#contractDepsList')[0]);
				});
				
				
				wait(false);
			});
			
			
			$.setDepsColums = async (_) => {
				wait();
				const sortedDeps = [];
				$("#contractDepsList").find('[sortdept]').each((k, item) => {
					let id = $(item).attr('sortdept');
					sortedDeps.push(id);
					
				});
				
				const {data, error, status, headers} = await axiosQuery('put', 'site/contracts/sortdeps', {sortedDeps});
				
				if (error) {
					$.notify('Ошибка установки столбцов!', 'error');
					console.log(error?.message, error.errors);
					wait(false);
				} else {
					$.notify('Столбцы успешно заданы!');
					
					getList({
						callback: function() {
							//$('[selectionsbtn]').ddrInputs('enable');
						}
					});
					close();
				}
			}
			
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Открыть окно с общей информацией (убрать отметку нового договора)
	$.openContractInfo = (tr, contractId) => {
		let isNew = $(tr).attr('isnew');
		if ($(event.target).hasAttr('noopen')) return;
		
		ddrPopup({
			width: 1100,
			buttons: ['Закрыть'],
			topClose: false,
			winClass: 'commoninfo'
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			
			// закрытие окна по нажатию клавиши ESC
			$(document).one('keydown', (e) => {
				if (e.keyCode == 27) {
					close();
				}
			});
			
			if (isNew) {
				Promise.all([
					axiosQuery('put', 'site/contracts/check_new', {contract_id: contractId}, 'json'),
					axiosQuery('get', 'site/contracts/common_info', {contract_id: contractId})
				]).then(([{data: checkNewData, error: checkNewError}, {data: commonInfoData, error: commonInfoError}]) => {
					checkNewContract(checkNewData, checkNewError, tr);
					getCommonInfo(commonInfoData, commonInfoError, contractId, setHtml);
					wait(false);
				});
			} else {
				axiosQuery('get', 'site/contracts/common_info', {contract_id: contractId}).then(({data, error, status, headers}) => {
					getCommonInfo(data, error, contractId, setHtml);
					wait(false);
				}).catch((e) => {
					console.log(e);
					wait(false);
				});
			}
		});
		
	}
	
	
	function checkNewContract(data, error, tr) {
		if (!data) {
			$.notify('Не удалось пометить договор как прочитанный!', 'error');
			console.log(error?.message, error?.errors);
			return;
		}
		
		$(tr).removeClass('clear bg-yellow-light');
	}
	
	function getCommonInfo(data, error, contractId, setHtml) {
		if (error) {
			$.notify('Не удалось получить информацию договора!', 'error');
			console.log(error?.message, error?.errors);
			return;
		}
		
		setHtml(data, null, () => {
			
			$('[tripleselect]').tripleTap((elem) => {
				selectText(elem);
			});
			
			$('#commonInfoFields').ddrInputs('change', (field) => {
				let fieldId = $(field).attr('commoninfofield'),
					fieldValue = $(field).val();
				
				axiosQuery('put', 'site/contracts/common_info', {contract_id: contractId, field_id: fieldId, value: fieldValue}, 'json')
					.then(({data, error, status, headers}) => {
						if (!data) {
							$.notify('Не удалось сохранить данные!', 'error');
							console.log(error?.message, error?.errors);
						}
					}).catch((e) => {
						console.log(e);
					});
			}, 300);
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Закрепить/открепить договор
	$.pinContract = (btn, contractId) => {
		let pinned = parseInt($(btn).attr('pinned'));
		
		axiosQuery('put', 'site/contracts/pin', {contract_id: contractId, stat: pinned}, 'json').then(({data, error, status, headers}) => {
			if (!data) {
				$.notify('Не удалось '+(pinned == 1 ? 'закрепить' : 'открепить')+' договор!', 'error');
				return;
			}
			if (pinned == 1) {
				$(btn).removeClass('color-gray-300 color-gray-500-hovered icon-hidden').addClass('color-gray-500');
				$(btn).setAttrib('pinned', '0');
			} else {
				$(btn).removeClass('color-gray-500').addClass('color-gray-300 color-gray-500-hovered icon-hidden');
				$(btn).setAttrib('pinned', '1');
			}
			
			$.notify('Договор успешно '+(pinned == 1 ? 'закреплен' : 'откреплен'));
			
		}).catch((e) => {
			console.log(e);
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	

	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Чат договора отпроавить сообщение
	$.chatSendMesage = (btn, contractId) => {
		let message = getContenteditable('#chatMessageBlock');
		if (!message) return;
		axiosQuery('put', 'site/contracts/chat', {contract_id: contractId, message}).then(({data, error, status, headers}) => {
			if (error) {
				$.notify('Не удалось отправить сообщение!', 'error');
				console.log(error?.message, error?.errors);
				return;
			}
			
			$('#chatMessageBlock').empty();
			$('#chatSendMesageBtn').ddrInputs('disable');
			$('#chatMessageList').append(data);
			sendMessStat = false;
			
			let chatVisibleHeight = $('#chatMessageList').outerHeight(),
				chatScrollHeight = $('#chatMessageList')[0].scrollHeight,
				scrollTop = chatScrollHeight - chatVisibleHeight;
			
			if (scrollTop > 0) {
				$('#chatMessageList').stop().animate({
					scrollTop: scrollTop,
				}, 200, 'swing', function() {
					
				});
			}
			
		}).catch((e) => {
			console.log(e);
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------------------------  Указание цвета статуса
	let statusesTooltip, contractId;
	$.openColorsStatuses = (btn, cId) => {
		contractId = cId;
		statusesTooltip = $(btn).ddrTooltip({
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
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------------------------  Заполнение данных договоров
	let contractSetDataTOut, oldInputId = null;
	$.contractSetData = (input, contractId, departmentId, stepId, type) => {
		let cell = $(input).closest('td');
		
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
				
				if (type == 1) {
					if (value == 1) {
						$(cell).css('background-color', 'transparent');
					} else {
						$(cell).css('background-color', $(cell).attr('deadlinecolor'));
					}
				}
				
				$(input).ddrInputs('removeClass', 'notouch');
				oldInputId = null;
			});
		}, 300);
	}
	
	
	
	
	
	
	//-------------------------------------------------  создать договор
	$.contractNew = () => {
		ddrPopup({
			title: 'Новый договор',
			width: 1300,
			buttons: ['Отмена', {action: 'contractStore', title: 'Создать', id: 'contractStoreBtn'}],
			disabledButtons: true, // при старте все кнопки кроме закрытия будут disabled
			closeByBackdrop: false, // Закрывать окно по фону либо только по [ddrpopupclose]
			winClass: 'ddrpopup_white'
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();
			
			axiosQuery('get', 'ajax/contracts/create', {views: 'admin.section.contracts.render.contracts', newItemIndex: 0, guard: 'site'})
				.then(({data, error, status, headers}) => {
					if (error) {
						$.notify(error.message, 'error');
						console.log(error?.message, error.errors);
					}
					
					if (data) setHtml(data, () => {
						enableButtons('close');
						$('input[name="price"]').number(true, 2, '.', ' ');
						$('input[name="price_nds"]').number(true, 2, '.', ' ');
						$('#genPrice').number(true, 2, '.', ' ');
						$('#genPriceNds').number(true, 2, '.', ' ');
						
						let genPercent = parseFloat($('#genPercent').val());
						
						$('#genPercent').on('input', function() {
							genPercent = parseFloat($(this).val());
							
							// меняется стоимость своего договора
							$('input[name="price"]').val(parseFloat($('#genPrice').val()) * ((100 - genPercent) / 100));
							$('input[name="price_nds"]').val(parseFloat($('#genPriceNds').val()) * ((100 - genPercent) / 100));
							
							// меняется стоимость не нашего договора
							//$('#genPrice').val(parseFloat($('input[name="price"]').val()) + ($('input[name="price"]').val() / 100) * genPercent);
							//$('#genPriceNds').val(parseFloat($('input[name="price_nds"]').val()) + ($('input[name="price_nds"]').val() / 100) * genPercent);
						});
						
						
						// Работа с НДС
						$('input[name="price"]').on('input', function() {
							let thisVal = parseFloat($(this).val());
							$('input[name="price_nds"]').val($.number((thisVal * (1 + priceNds / 100)), 2, '.', ' '));
							$('#genPrice').val($.number(thisVal / ((100 - genPercent) / 100), 2, '.', ' '));
							$('#genPriceNds').val($.number(thisVal / ((100 - genPercent) / 100) * (1 + priceNds / 100), 2, '.', ' '));
						});
						
						
						$('input[name="price_nds"]').on('input', function() {
							let thisVal = parseFloat($(this).val());
							$('input[name="price"]').val($.number((thisVal / (1 + priceNds / 100)), 2, '.', ' '));
							$('#genPrice').val($.number(thisVal / ((100 - genPercent) / 100) / (1 + priceNds / 100), 2, '.', ' '));
							$('#genPriceNds').val($.number(thisVal / ((100 - genPercent) / 100), 2, '.', ' '));
						});
						
						
						$('#genPrice').on('input', function() {
							let thisVal = parseFloat($(this).val());
							$('#genPriceNds').val($.number((thisVal * (1 + priceNds / 100)), 2, '.', ' '));
							$('input[name="price"]').val($.number(thisVal * ((100 - genPercent) / 100), 2, '.', ' '));
							$('input[name="price_nds"]').val($.number(thisVal * ((100 - genPercent) / 100) * (1 + priceNds / 100), 2, '.', ' '));
						});
						
						$('#genPriceNds').on('input', function() {
							let thisVal = parseFloat($(this).val());
							$('#genPrice').val($.number((thisVal / (1 + priceNds / 100)), 2, '.', ' '));
							$('input[name="price"]').val($.number(thisVal * ((100 - genPercent) / 100) / (1 + priceNds / 100), 2, '.', ' '));
							$('input[name="price_nds"]').val($.number(thisVal * ((100 - genPercent) / 100), 2, '.', ' '));
						});
						
						
						
						
						
						$('#subcontracting').on('change', function() {
							let isChecked = $(this).is(':checked');
							if (isChecked) $('#genFields').removeAttrib('hidden');
							else {
								$('#genFields').setAttrib('hidden');
								$('#genPercent').val('');
								$('#genPrice').val('');
								$('#genPriceNds').val('');
							}
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
								
							} else {
								$('input[name="buy_number"]').ddrInputs('enable');
								$('input[name="buy_number"]').val('');
								$('#dateBuyField').ddrInputs('enable');
							}
						});
						
						
						
						
						$('#contractForm').ddrInputs('change', function(item) {
							enableButtons(false);
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
					if (error.errors) {
						console.log(error.errors);
						$.each(error.errors, function(field, errors) {
							if (field == 'object_number') {
								$('#contractStoreBtn').ddrInputs('disable');
								$.notify('Нельзя создать договор с таким номером объекта', 'error');
							} 
							
							$(formSelector).find('[name="'+field+'"]').ddrInputs('error', errors[0]);
						});
					}
					
					if (error) {
						$.notify(error.message, 'error');
						wait(false);
					}
					
					if (data) {
						$.notify('Договор успешно создан!');
						if (currentList != -1) getList();
						close();
					}
					
				}).catch((e) => {
					console.log(e);
				});
				
			}
		});	
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	let haSContextMenu = false;
	//----------------------------------------------------------------------------------- Выделение договоров
	$('#contractsTable').on(tapEvent, '[contractid]', function({type, target, currentTarget, ctrlKey, shiftKey, detail, which, metaKey}) {
		let row = currentTarget,
			contractId = $(row).attr('contractid'),
			isCommon = !!$(target).closest('[ddrtabletd]').hasAttr('commonlist') || false;
		
		
		if (haSContextMenu) {
			selectedContracts.clear();
			haSContextMenu = false; 
			lastChoosedRow = null;
		}
		
		if (!isCommon) {
			$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
			lastChoosedRow = null;
			selectedContracts.clear();
			return;
		} 
			
		if (ctrlKey || metaKey) {
			if ($(row).hasAttr('contractselected')) {
				$(row).removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
				let lastSelected = selectedContracts.remove($(row).attr('contractid'));
				lastChoosedRow = row;
			} else {
				$(row).addClass('ddrtable__tr-selected').setAttrib('contractselected');
				lastChoosedRow = row;
				selectedContracts.append($(row).attr('contractid'));
			}
		} 
		
		
		
		if (shiftKey) {
			if (lastChoosedRow) {
				let lastChoosedContractId = $(lastChoosedRow).attr('contractid');
				if (contractId == lastChoosedContractId) {
					$(row).not('[contractselected]').addClass('ddrtable__tr-selected').setAttrib('contractselected');
					selectedContracts.add(contractId);
				} else {
					let choosedItems = loadedContractsIds.get(lastChoosedContractId, contractId);
					if (choosedItems.length) {
						$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
						choosedItems.forEach(function(contractId) {
							$('#contractsTable').find('[contractid="'+contractId+'"]').addClass('ddrtable__tr-selected').setAttrib('contractselected');
						});
						selectedContracts.add(choosedItems);
					}
				}
				
			} else {
				$(row).not('[contractselected]').addClass('ddrtable__tr-selected').setAttrib('contractselected');
				lastChoosedRow = row;
				selectedContracts.add($(lastChoosedRow).attr('contractid'));
			}
		}
		
		
		
		if (!ctrlKey && !metaKey && !shiftKey) {
			$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
			lastChoosedRow = null;
			selectedContracts.clear();
		}
		
		console.log('Выделение договоров', selectedContracts.items);
		//console.log(type, target, currentTarget, ctrlKey, shiftKey, detail, which);
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------- Контекстное меню
	
	let sendMessStat;
	$.contractContextMenu = (
		{target, closeOnScroll, onContextMenu, changeAttrData, buildTitle},
		contractId,
		departmentId,
		selectionId,
		objectNumber,
		title,
		
		hasDepsToSend,
		messagesCount,
		searched,
		selectionEdited,
		isArchive,
		
		canToArchive, // отправка договора в архив
		canSending, // отправка договора в другой отдел из отдела
		canSendingAll, // отправка договора в другой отдел из общего списка
		canHiding, // скрыть договор
		canChat, // просмотр чата
		canChatSending, // возможность отправлять сообщения в чате
		canReturnToWork // вернуть договор в работу из архива
		) => {
		
		
		const isCommon = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('commonlist') || false;
		const isDeptCheckbox = !!$(target.pointer).closest('[ddrtabletd]').hasAttr('deptcheck') || false;
		const hasCheckbox = !!$(target.pointer).closest('[ddrtabletd]').children().length
		
		onContextMenu(() => {
			haSContextMenu = true;
			
			// если кликнуть на НЕвыделенном договоре - то все выделенния отменятся и выделится текущий кликнутый договор
			if (isCommon && $(target.selector).hasAttr('contractselected') == false) {
				$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
				//lastChoosedRow = target.selector;
				//$(target.selector).addClass('ddrtable__tr-selected').setAttrib('contractselected');
				selectedContracts.add($(target.selector).attr('contractid'));
			}
			
			// Если клик НЕ на таблице общего перечня
			if (!isCommon) {
				$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
				// lastChoosedRow = target.selector;
				// $(target.selector).addClass('ddrtable__tr-selected').setAttrib('contractselected');
				//selectedContracts.add($(target.selector).attr('contractid'));
				
			}
			// console.log(selectedContracts.items);
		});
		
		
		const countSelected = selectedContracts.items?.length || null;
		
		
		closeOnScroll('#contractsList');
		
		
		return [
			{
				name: buildTitle(countSelected, 'Чат договора', 'Cообщение в чаты'),
				countLeft: countSelected > 1 ? countSelected : null,
				countRight: countSelected == 1 ? messagesCount : null,
				countOnArrow: true,
				visible: isCommon && canChat,
				sort: 1,
				onClick() {
					if (countSelected == 1) { // Если выделен 1 договор
						ddrPopup({
							title: '<small class="fz12px color-gray">Чат договора:</small> «'+title+'»',
							width: 800,
							buttons: ['Закрыть'],
							winClass: 'ddrpopup_chat'
						}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => {
							wait();
							
							axiosQuery('get', 'site/contracts/chat', {contract_id: contractId}).then(({data, error, status, headers}) => {
								
								if (error) {
									$.notify('Не удалось загрузить чат!', 'error');
									console.log(error?.message, error?.errors);
									return;
								}
								
								setHtml(data, () => {
									sendMessStat = false;
									wait(false);
									
									$('.chat__message').tripleTap((elem) => {
										selectText(elem);
									});
									
									let chatVisibleHeight = $('#chatMessageList').outerHeight(),
										chatScrollHeight = $('#chatMessageList')[0].scrollHeight;
									$('#chatMessageList').scrollTop(chatScrollHeight - chatVisibleHeight);
									
									$('#chatMessageBlock').focus();
									
									$('#chatMessageBlock').ddrInputs('change', () => {
										let mess = getContenteditable('#chatMessageBlock');
										
										if (mess && !sendMessStat) {
											sendMessStat = true;
											$('#chatSendMesageBtn').ddrInputs('enable');
										} else if (!mess && sendMessStat) {
											sendMessStat = false;
											$('#chatSendMesageBtn').ddrInputs('disable');
										}
									});
								});
								
							}).catch((e) => {
								console.log(e);
							});
							
						});
					
					} else { // Если выделено более 1 договора - отправить сообщение в чаты с выделеными договорами
						
						let html = '<p class="d-block mb5px fz14px color-darkgray">Сообщение:</p>' +
									'<div class="textarea normal-textarea w100" id="sendMessagesToManyContractsField">' +
										'<textarea name="" rows="10" class="w100"></textarea>' +
									'</div>';
						
						ddrPopup({
							title: 'Отправить сообщение в выбранные договоры',
							width: 500,
							html,
							buttons: ['Закрыть', {title: 'Отправить', variant: 'blue', action: 'sendMessagesToManyContracts', disabled: 1, id: 'sendMessagesToManyContractsBtn'}],
							winClass: 'ddrpopup_chat'
						}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => {							
							let isEmpty = true;	
							$('#sendMessagesToManyContractsField').ddrInputs('change', (textarea) => {
								if ($(textarea).val() && isEmpty) {
									$('#sendMessagesToManyContractsBtn').ddrInputs('enable');
									isEmpty = false;
								} else if (!$(textarea).val() && !isEmpty) {
									$('#sendMessagesToManyContractsBtn').ddrInputs('disable');
									isEmpty = true;
								}
							});
							
							
							$.sendMessagesToManyContracts = () => {
								wait();
								$('#sendMessagesToManyContractsBtn').ddrInputs('disable');
								
								let message = $('#sendMessagesToManyContractsField').find('textarea').val();
								let sendMessAbortCtrl = new AbortController();
								
								axiosQuery('put', 'site/contracts/chats', {contractIds: selectedContracts.items, message}, 'json', sendMessAbortCtrl)
								.then(({data, error, status, headers}) => {
									if (error) {
										$.notify('Ошибка отправки сообщения!', 'error');
										return;
									}
									
									if (data) {
										if (data == -1) {
											$.notify('Сообщение не было разослано!', 'info');
										} else {
											$.notify('Сообщение успешно отправлено во все чаты выбранных договоров!');
											close();
										}
										
									} else {
										$.notify('Не удалось отправить сообщение в чаты выбранных договоров!', 'error');
										wait(false);
									}
									
								}).catch((e) => {
									console.log(e);
								});
								
								
								onClose(() => {
									sendMessAbortCtrl.abort();
								});
							}
						});
					}
				}
			}, {
				name: 'Отправить в архив',
				visible: isCommon && (canToArchive && !isArchive),
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 5,
				onClick: () => {
					let html = '';
					html += '<div>';
					if (countSelected == 1) {
						html += '<p class="fz14px color-darkgray text-start">Номер объекта: <span class="color-black">'+objectNumber+'</span></p>';
						html += '<p class="fz14px color-darkgray text-start">Название/заявитель: <span class="color-black">'+title+'</span></p>';
						html += '<p class="fz18px color-red mt15px">Вы действительно хотите отправить договор в архив?</p>';
					} else if (countSelected > 1) {
						html += '<p class="fz18px color-red mt15px">'+buildTitle(countSelected, 'Вы действительно хотите отправить # % в архив?', ['договор', 'договора', 'договоров'])+'</p>';
					}
					html += '</div>';
					
					ddrPopup({
						width: 400, // ширина окна
						html, // контент
						buttons: ['ui.cancel', {title: 'Отправить', variant: 'red', action: 'contractToArchiveAction'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.contractToArchiveAction = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/to_archive', {contractIds: selectedContracts.items}, 'json')
							.then(({data, error, status, headers}) => {
								if (data) {
									if (selectionId || searched) {
										getCounts(() => {
											removeContractsRows(target);
										});
									} else {
										removeContractsRows(target);
									}
									
									$.notify(buildTitle(countSelected, '% успешно отправлен в архив!', '# % успешно отправлены в архив!', ['Договор', 'договора', 'договоров']));
								} else {
									$.notify(buildTitle(countSelected, 'Ошибка! % не был отправлен в архив!', 'Ошибка! % не были отправлены в архив!', ['договор', 'договора', 'договоров']), 'error');
								}
								close();
							});
						}
					});
				},
			}, {
				name: 'Вернуть в работу',
				visible: isCommon && canReturnToWork && isArchive,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 5,
				onClick() {
					ddrPopup({
						width: 400, // ширина окна
						html: '<p class="fz18px color-green">'+buildTitle(countSelected, 'Вы действительно хотите вернуть # % в работу?', ['договор', 'договора', 'договоров'])+'</p>', // контент
						buttons: ['ui.cancel', {title: 'Вернуть', variant: 'blue', action: 'returnContractToWorkBtn'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.returnContractToWorkBtn = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/to_work', {contractIds: selectedContracts.items}, 'json')
							.then(({data, error, status, headers}) => {
								if (data) {
									if (selectionId || searched) {
										getCounts(() => {
											removeContractsRows(target);
										});
									} else {
										removeContractsRows(target);
									}
									
									$.notify(buildTitle(countSelected, '% успешно возвращен в работу!', '# % успешно возвращены в работу!', ['Договор', 'договора', 'договоров']));
									//target.changeAttrData(15, '0');
								} else {
									$.notify(buildTitle(countSelected, 'Ошибка! % не был возвращен в работу!', 'Ошибка! # % не были возвращены в работу!', ['Договор', 'договора', 'договоров']), 'error');
								}
								close();
							});
						}
					});
				}
			}, {
				name: 'Добавить в подборку',
				//hidden: selectionId,
				visible: isCommon,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 2,
				load: {
					url: 'site/contracts/selections_to_choose',
					params: {contractIds: selectedContracts.items},
					method: 'get',
					map: (item) => {
						return {
							name: item.title,
							//faIcon: 'fa-solid fa-clipboard-check',
							disabled: !!item.choosed,
							onClick(selector) {
								let selectionId = item.id;
								
								let procNotif = processNotify(buildTitle(countSelected, 'Добавление # % в подборку...', ['договора', 'договоров', 'договоров']));
								
								let contractIds = selectedContracts.items;
								
								let params, method;
								
								if (contractIds.length == 1) {
									params = {contractId: contractIds[0], selectionId};
									method = 'add_contract';
								} else {
									params = {contractIds, selectionId};
									method = 'add_contracts';
								}
								
								axiosQuery('put', 'site/selections/'+method, params)
								.then(({data, error, status, headers}) => {
									if (error) {
										procNotif.error({message: 'Ошибка добавления в подборку!'});
										console.log(error?.message, error.errors);
									} else {
										procNotif.done({message: buildTitle(countSelected, '% успешно добавлен в подборку!', '# % успешно добавлены в подборку!', ['Договор', 'договора', 'договоров'])});
									}
								});
							}
						};
					}
				},
			}, {
				name: 'Удалить из подборки',
				visible: isCommon && selectionId,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 4,
				onClick() {		
					let procNotif = processNotify(buildTitle(countSelected, 'Удаление # % из подборки...', ['договора', 'договоров', 'договоров']));
					
					let contractIds = selectedContracts.items;
					
					let params, method;
					
					if (contractIds.length == 1) {
						params = {contractId: contractIds[0], selectionId};
						method = 'remove_contract';
					} else {
						params = {contractIds, selectionId};
						method = 'remove_contracts';
					}
					
					axiosQuery('put', 'site/selections/'+method, params)
					.then(({data, error, status, headers}) => {
						if (error) {
							//$.notify('Ошибка удаления из подборки!', 'error');
							procNotif.error({message: 'Ошибка удаления договора из подборки!'});
							console.log(error?.message, error.errors);
						} else {
							procNotif.done({message: buildTitle(countSelected, '% успешно удален из подборки!', '# % успешно удалены из подборки!', ['Договор', 'договора', 'договоров'])});
							//target.changeAttrData(7, '0');
							
							getCounts(() => {
								removeContractsRows(target);
								$('[selectionsbtn]').ddrInputs('enable');
							});
						}
					});
				}
			}, {
				name: 'Создать новую подборку',
				sort: 3,
				visible: isCommon,
				countLeft: countSelected > 1 ? countSelected : null,
				onClick() {
					let html = 	'<p class="d-block mb5px fz14px color-darkgray">Название подборки:</p>' +
								'<div class="input normal-input normal-input-text w100">' +
									'<input type="text" value="" id="selectionNameInput" placeholder="Введите текст" autocomplete="off" inpgroup="normal">' +
									'<div class="normal-input__errorlabel noselect" errorlabel=""></div>' +
								'</div>';
					
					
					ddrPopup({
						title: 'Создать подборку из выбранных договоров',
						width: 500,
						html,
						buttons: ['Закрыть', {title: 'Создать', variant: 'blue', action: 'createNewSelection', disabled: 1, id: 'createNewSelectionBtn'}],
						winClass: 'ddrpopup_chat'
					}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => {							
						
						let isEmpty = true;	
						$('#selectionNameInput').ddrInputs('change', (input) => {
							if ($(input).val() && isEmpty) {
								$('#createNewSelectionBtn').ddrInputs('enable');
								isEmpty = false;
							} else if (!$(input).val() && !isEmpty) {
								$('#createNewSelectionBtn').ddrInputs('disable');
								isEmpty = true;
							}
						});
						
						
						$.createNewSelection = () => {
							wait();
							
							let title = $('#selectionNameInput').val();
							let newSelectionAbortCtrl = new AbortController();
							
							axiosQuery('post', 'site/selections/add_selection_from_contextmenu', {title, contractIds: selectedContracts.items}, 'json', newSelectionAbortCtrl)
							.then(({data, error, status, headers}) => {
								if (error) {
									$.notify('Ошибка создания подборки!', 'error');
									return;
								}
								
								if (data) {
									if (data == -1) {
										$.notify('Подборка не была создана!', 'info');
										wait(false);
									} else {
										$.notify(buildTitle(countSelected, 'Поодборка с # % была успешна создана!', ['договором', 'договорами', 'договорами']));
										close();
									}
									
								} else {
									$.notify('Не удалось создать подборку!', 'error');
									wait(false);
								}
								
							}).catch((e) => {
								console.log(e);
							});
							
							onClose(() => {
								newSelectionAbortCtrl.abort();
							});
						}
						
					});
				}
			}, {
				name: 'Отправить в другой отдел',
				enabled: selectedContracts.items.length > 1 || (!!hasDepsToSend && ((canSending && departmentId) || (canSendingAll && !departmentId))),
				hidden: isArchive || !isCommon,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 4,
				load: {
					url: 'site/contracts/departments',
					params: {contractId: selectedContracts.items.length == 1 ? selectedContracts.items[0] : null},
					method: 'get',
					map: (item) => {
						return {
							name: item.name,
							//faIcon: 'fa-solid fa-angles-right',
							visible: true,
							onClick(selector) {
								let departmentName = selector.text(),
									itemsCount = selector.items().length;
								
								let procNotif = processNotify('Отправка договора в другой отдел...');
								
								axiosQuery('post', 'site/contracts/send', {contractIds: selectedContracts.items, departmentId: item.id}, 'json')
								.then(({data, error, status, headers}) => {
									if (data) {
										//$.notify('Договор успешно отправлен в '+departmentName+'!');
										
										if (selectionId || searched) {
											let params = {};
											getCounts(() => {
												removeContractsRows(target);
											});
										} else {
											removeContractsRows(target);
										}
										
										if (data.length) {
											let mess = buildTitle(data.length, '# % успешно отправлен в '+departmentName+'!', '# % успешно отправлены в '+departmentName+'!', ['договор', 'договора', 'договоров']);
											procNotif.done({message: mess});
										} else {
											procNotif.error({message: 'Ни один договор не был отправлен!'});
										} 
										
										if (countSelected == 1 && itemsCount == 0) changeAttrData(6, '0');
									} else {
										//$.notify('Ошибка! Договор не был отправлен!', 'error');
										procNotif.error({message: 'Ошибка! Договор не был отправлен!'});
									}
								});
							}
						};
					}
				},
			}, {
				name: 'Скрыть',
				visible: isCommon && canHiding && departmentId && !isArchive,
				countLeft: countSelected > 1 ? countSelected : null,
				sort: 6,
				onClick() {	
					ddrPopup({
						width: 400, // ширина окна
						html: buildTitle(countSelected, '<p class="fz18px color-red">Вы действительно хотите скрыть # %?</p>', ['договор', 'договора', 'договоров']), // контент
						buttons: ['ui.cancel', {title: 'Скрыть', variant: 'red', action: 'contractHide'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.contractHide = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/hide', {contractIds: selectedContracts.items, departmentId}, 'json')
							.then(({data, error, status, headers}) => {
								if (data) {
									if (selectionId || searched) {
										getCounts(() => {
											removeContractsRows(target);
										});
									} else {
										removeContractsRows(target);
									}
									
									$.notify(buildTitle(countSelected, '% успешно скрыт!', '# % успешно скрыты!', ['Договор', 'договора', 'договоров']));
									//target.changeAttrData(9, '0');
								} else {
									$.notify(buildTitle(countSelected, 'Ошибка! договор не был скрыт!', 'Ошибка! договоры не были скрыты!'), 'error');
								}
								close();
							});
						}
					});
				}
			}, {
				name: hasCheckbox && canRemoveCheckbox ? 'Удалить чекбокс' : (!hasCheckbox && canCreateCheckbox ? 'Добавить чекбокс' : ''),
				visible: isDeptCheckbox && !isArchive && ((!hasCheckbox && canCreateCheckbox) || (hasCheckbox && canRemoveCheckbox)),
				sort: 1,
				async onClick(foo) {	
					const cell = $(target.pointer).closest('[ddrtabletd]');
					const edited = !!$(cell).attr('edited');
					const attrData = $(cell).attr('deptcheck');
					const [contractId = null, departmentId = null, stepId = null] = pregSplit(attrData);
					
					const waitCell = $(cell).ddrWait({
						iconHeight: '30px',
						bgColor: '#efe9f9',
					});
					
					const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/step_checkbox', {
						contractId, 
						departmentId,
						stepId,
						value: hasCheckbox
					}, 'json');
					
					
					if (error) {
						console.log(error);
						$.notify('Ошибка! Не удалось '+(hasCheckbox ? 'удалить' : 'добавить')+' чекбокс!', 'error');
						waitCell.destroy();
						return;
					}
					
					// canCreateCheckbox canRemoveCheckbox
					if (data) {
						if (!hasCheckbox) {
							if (edited) {
								const randId = generateCode('nnnnnnn');
								const editedCheckbox = '<div class="checkbox normal-checkbox">' +
									'<input type="checkbox" name="assigned_primary" id="checkbox'+randId+'" inpgroup="normal" oninput="$.contractSetData(this, '+contractId+','+departmentId+','+stepId+',1)">' +
									
										'<label class="noselect" for="checkbox'+randId+'"></label>' +
									
										'<label for="checkbox'+randId+'" class="checkbox__label lh90 d-inline-block normal-checkbox__label noselect"></label>' +
										'<div class="normal-checkbox__errorlabel" errorlabel=""></div>' +
									'</div>';
								$(cell).html(editedCheckbox);
							} else {
								$(cell).html('<div class="checkbox-empty checkbox-empty-normal border-gray-400"></div>');
							}
							$.notify('Чекбокс успешно добавлен!');
						} else {
							$(cell).empty();
							$.notify('Чекбокс успешно удален!');
						}
						waitCell.destroy();
					}
				}
			}
		];
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Фильтрация по полю
	
	let filterByDateTooltip,
		dateFromPicker,
		dateToPicker,
		dateFromValue = {},
		dateToValue = {},
		columnDateFilter;
	
	
	$.contractFilterBy = async ({target, preload, closeOnScroll, onContextMenu, onCloseContextMenu, changeAttrData, buildTitle}, column) => {
		if (abortCtrlFilter instanceof AbortController) abortCtrlFilter.abort();
		ddrCssVar('cm-mainFontSize', '12px');
		ddrCssVar('cm-mainMinHeight', '30px');
		
		preload({iconSize: '3rem'});
		
		onContextMenu(({setMaxHeight}) => {
			setMaxHeight('calc(100vh - 300px)');
		});
		
		
		
		abortCtrlFilter = new AbortController();
		const {data, error, status, headers, abort} = await axiosQuery('get', 'site/contracts/column_values', {column, currentList}, 'json', abortCtrlFilter);
		
		const navData = [];
		$.each(data, (_, {id, name, title}) => {
			let itemTitle = name || title;
			
			let itemName;
			if (columnFilter?.value == id) {
				itemName = '<span class="color-blue">'+itemTitle+'</span>';
			} else {
				itemName = itemTitle;
			}
			
			navData.push({
				name: itemName,
				onClick() {
					columnFilter = {
						column,
						value: id
					};
					
					dateFromValue = {},
					dateToValue = {},
					
					getList({
						withCounts: search || selection,
						callback: function() {}
					});
				}
			});
		});
		
		
		
		onCloseContextMenu(() => {
			ddrCssVar('cm-mainFontSize', '16px');
			ddrCssVar('cm-mainMinHeight', '48px');
		});
		
		return navData;
	}
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Фильтрация по дате
	$.contractFilterByDate = (column) => {
		
		if (filterByDateTooltip?.destroy != undefined) filterByDateTooltip.destroy();
		if (dateFromPicker?.remove != undefined) dateFromPicker.remove();
		if (dateToPicker?.remove != undefined) dateToPicker.remove();
		
		columnDateFilter = column;
		
		filterByDateTooltip = $(event.currentTarget).ddrTooltip({
			//cls: 'w44rem',
			placement: 'bottom',
			tag: 'noscroll',
			minWidth: '430px',
			minHeight: '225px',
			duration: [200, 200],
			trigger: 'click',
			wait: {
				iconHeight: '40px'
			},
			onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
				
				//abortCtrlFilterDates = new AbortController();
				//const {data, error, status, headers, abort} = await axiosQuery('get', 'site/contracts/calendar', {}, 'text', abortCtrlFilterDates);
				
				let disabledBtn = !dateFromValue[column] && !dateToValue[column] ? 'disabled' : '';
				
				const calendarHtml = '<div class="mt5px">' +
					'<div class="row row-cols-2 g-10">' +
						'<div class="col">' +
							'<strong class="form__label color-dark fz12px lh90 mb4px">Дата от:</strong>' +
							'<input type="hidden" id="filterDateFrom" />' +
							'<div></div>' +
						'</div>' +
						'<div>' +
							'<strong class="form__label color-dark fz12px lh90 mb4px">Дата до:</strong>' +
							'<input type="hidden" id="filterDateTo" />' +
							'<div></div>' +
						'</div>' +
					'</div>' +
					'<div class="d-flex justify-content-end mt5px" style="position:absolute;right:10px;bottom:7px;width:100%;">' +
						'<div class="button verysmall-button button-yellow">' +
							'<button inpgroup="verysmall" onclick="$.setContractFilterByDate(\''+column+'\')" id="dateToPickerBtn" '+disabledBtn+'>Применить</button>' +
						'</div>' +
					'</div>' +
				'</div>';
				
				await setData(calendarHtml);
				
				dateFromPicker = _setDatePicker('filterDateFrom', dateFromValue[column]);
				dateToPicker = _setDatePicker('filterDateTo', dateToValue[column]);
				
				waitDetroy();
				// initD.getFullYear()+'-'+addZero(initD.getMonth() + 1)+'-'+addZero(initD.getDate()),
				
				function _setDatePicker(id = null, initD = null) {
					if (id == null) return;
					const datePicker = ddrDatepicker('#'+id, {
						id: 'filterDatePeriod',
						alwaysShow: true,
						position: false,
						startDay: 1,
						defaultView: 'calendar',
						overlayPlaceholder: 'Введите год',
						customDays: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
						customMonths: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
						dateSelected: initD ? new Date(initD) : null,
						
						onShow: ({calendar}) => {
							$(calendar).parent('.qs-datepicker-container').css({
								'box-shadow': 'none',
								'position': 'static',
								'width': '220px',
							});
							
							$(calendar).css({
								'position': 'relative',
							});
							
						    // Do stuff when the calendar is shown.
						    // You have access to the datepicker instance for convenience.
						},
						onSelect: (instance, date) => {
							
							if (date) $(instance.el).attr('date', date);
							else $(instance.el).removeAttrib('date');
							
							let fDate = $('input#filterDateFrom').attr('date') || false,
								tDate = $('input#filterDateTo').attr('date') || false;
							
							if (fDate || tDate) {
								$('#dateToPickerBtn').removeAttrib('disabled');
							} else {
								$('#dateToPickerBtn').setAttrib('disabled');
							}
						},
						formatter: (input, cd, instance) => {
							//$(input).setAttrib('date', addZero(cd.getDate())+'-'+addZero(cd.getMonth() + 1)+'-'+cd.getFullYear());
							$(input).setAttrib('date', cd);
						},
						
					});
					return datePicker;
				}
				
				
			}
		});
	}
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Применить фильтр
	$.setContractFilterByDate = async (column) => {
		
		dateFromValue[column] = $('input#filterDateFrom').attr('date'),
		dateToValue[column] = $('input#filterDateTo').attr('date');
		
		const buildData = [];
		
		
		if (dateFromValue[column]) {
			const fd = new Date(dateFromValue[column]);
			buildData.push(fd.getFullYear()+'-'+addZero(fd.getMonth() + 1)+'-'+addZero(fd.getDate())+' 00:00:00');
		} else buildData.push('');
		
		if (dateToValue[column]) {
			const td = new Date(dateToValue[column]);
			buildData.push(td.getFullYear()+'-'+addZero(td.getMonth() + 1)+'-'+addZero(td.getDate())+' 00:00:00');
		} else buildData.push('');
		
		
		columnFilter = {
			column: columnDateFilter,
			value: buildData.join('|'), //dateFromValueFormat+'|'+dateToValueFormat,
		};
		
		getList({
			withCounts: search || selection,
			callback: function() {}
		});
		
		filterByDateTooltip.destroy();
		columnDateFilter = null;
		dateFromPicker.remove()
		dateToPicker.remove();
	}
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Отменить фильтрацию
	$.cancelContractFilter = (event, column) => {
		if (!column) {
			columnFilter = null;
			dateFromValue = {};
			dateToValue = {};
			columnDateFilter = null;
		} else {
			event.stopPropagation();
			columnFilter = null;
			
			delete(dateFromValue[column]);
			delete(dateToValue[column]);
			
			if (filterByDateTooltip?.remove) filterByDateTooltip.destroy();
			columnDateFilter = null;
			
			if (dateFromPicker?.remove) dateFromPicker.remove()
			if (dateToPicker?.remove) dateToPicker.remove();
		}
		
		
		getList({
			withCounts: search || selection,
			callback: function() {}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Тултип "комментарии"
	let commentsTooltip, commentsTooltipTOut;
	$.commentsTooltip = (event) => {
		const {target} = event;
		const cell = $(target).closest('[ddrtabletd]');
		const hasCheckbox = !!$(target).closest('[ddrtabletd]').children().length;
		
		if ($(cell).hasAttr('tooltiped') || !hasCheckbox) return;
		
		clearTimeout(commentsTooltipTOut);
		
		if (commentsTooltip?.destroy != undefined) commentsTooltip.destroy();
		
		commentsTooltipTOut = setTimeout(async () => {
			$(cell).setAttrib('tooltiped');
			const attrData = $(cell).attr('deptcheck');
			const [contractId = null, departmentId = null, stepId = null] = pregSplit(attrData);
			
			$('#contractsList').one('scroll', function() {
				// При скролле списка скрыть тултип комментариев
				if (commentsTooltip?.destroy != undefined) commentsTooltip.destroy();
			});
			
			
			commentsTooltip = $(cell).ddrTooltip({
				//cls: 'w44rem',
				placement: 'bottom',
				tag: 'noscroll noopen',
				offset: [0 -5],
				minWidth: '430px',
				minHeight: '225px',
				duration: [200, 200],
				trigger: 'click',
				wait: {
					iconHeight: '40px'
				},
				onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
					
					const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_comment', {
						contract_id: contractId, 
						department_id: departmentId,
						step_id: stepId,
					}, 'json');
					
					await setData($(data).html());
					
					waitDetroy();
					
					let inputCellCommentTOut;
					$(popper).find('#sendCellComment').on('input', function() {
						clearTimeout(inputCellCommentTOut);
						inputCellCommentTOut = setTimeout(async () => {
							const comment = $(this).val();
							const {data: postRes, error: postErr, status, headers} = await axiosQuery('post', 'site/contracts/cell_comment', {
								contract_id: contractId, 
								department_id: departmentId,
								step_id: stepId,
								comment,
							}, 'json');
							
							if (postErr) {
								console.log(postErr);
								$.notify('Ошибка! Не удалось задать комментарий!', 'error');
								return;
							}
							
							if (postRes) {
								$.notify('Комментарий успешно сохранен!');
							}
							
						}, 500);
					});
				},
				onDestroy: function() {
					$(cell).removeAttrib('tooltiped');
				}
			});
			
			
			
			// закрытие окна по нажатию клавиши ESC
			$(document).one('keydown', (e) => {
				if (e.keyCode == 27 && commentsTooltip?.destroy != undefined) {
					commentsTooltip.destroy();
				}
			});
		}, 1000);
	}
	
	
	$.commentsTooltipLeave = (event) => {
		clearTimeout(commentsTooltipTOut);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Получение записей
	
	
	function getList(settings) {
		if (abortCtrl instanceof AbortController) abortCtrl.abort();
		
		let {
			init,
			withCounts,
			append,
			offset: localOffset,
			callback
		} = _.assign({
			init: false,
			withCounts: false,
			append: false,
			offset: null,
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
		
		
		if (!append) {
			offset = 0;
			$(document).scrollTop(0);
			lastChoosedRow = null;
			selectedContracts.clear();
			loadedContractsIds.clear();
		} else {}
		
		
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
		params['limit'] = limit;
		params['offset'] = localOffset != null ? localOffset : offset;
		params['append'] = append ? 1 : 0;
		params['search'] = search;
		params['filter'] = columnFilter;
		params['selection'] = selection;
		params['edit_selection'] = editSelection;
		params['selected_contracts'] = selectedContracts.items;
		
		
		//-----------------------------------
		
		if (!init) {
			listWait = $('#contractsTable').ddrWait({
				iconHeight: '40px',
				text: 'Загрузка',
				fontSize: '14px',
				bgColor: '#ffffffbb',
				//position: 'adaptive'
			});
		} else {}
		
		
		abortCtrl = new AbortController();
		axiosQuery('get', 'site/contracts', params, 'text', abortCtrl).then(({data, error, status, headers, abort}) => {
			if (error) {
				$.notify(error.message, 'error');
				return;
			}
			
			const currentCount = headers ? headers['x-count-contracts-current'] : null;
			const contractIds = headers ? headers['x-contracts-ids'] : null;
			
			loadedContractsIds.add(contractIds);
			
			
			if (append) {
				if (currentCount) {
					if (append == 'prepend') {
						$('#contractsList').blockTable('prependData', data, limit);
					} else if (append == 'append') {
						$('#contractsList').blockTable('appendData', data);
					}
				}
				
			} else {
				if (currentList && headers['x-count-contracts-departments']) {
					let depsCounts = JSON.parse(headers['x-count-contracts-departments']);
					totalCount = depsCounts[currentList]|| null;
				} else {
					totalCount = headers['x-count-contracts-all'] || null;
				}
				
				$('#contractsTable').html(data);
			}
			
			
			

			const showTotal = headers['x-count-contracts-current'] && ((params['offset'] + params['limit'] >= totalCount) || (totalCount <= params['limit']));
			if (showTotal) {
				
				const showTotalHtml = 	'<div class="ddrtable__tr h5rem-4px ddrtable__tr_visible" style="position:relative;" ddrtabletr>' +
											'<div class="totalcount" id="totalCountBlock">' +
												'<p class="totalcount__value">Всего договоров '+totalCount+'</p>' +
											'</div>' +
										'</div>';
				
				$('#contractsList').find('[ddrtabletr]:last').after(showTotalHtml);
				$(".horisontal").on("scroll", function (e) {
				    let horizontal = e.currentTarget.scrollLeft;
				    $('#totalCountBlock').css('left', horizontal+'px');
				});
			}
			
			
			
			
			if (selection) {
				$('#tableContainer').removeClass('mb2rem').addClass('mb4rem');
			} else {
				$('#tableContainer').removeClass('mb4rem').addClass('mb2rem');
			}
			
			
			
			
			if (init) $('#contractsCard').card('ready');
			else listWait.destroy();
			
			if (abort) listWait.destroy();
			
			if (search && currentCount) {
				let findSubStr = $('#contractsTable').find('p:icontains("'+search+'")');
				if (findSubStr) {
					$.each(findSubStr, function(k, item) {
						$(item).html($(item).text().replace(new RegExp("(" + preg_quote(search) + ")", 'gi'), '<span class="highlight">$1</span>'));
					});
				}
			}	
			
			if (currentCount) $('[stepprice]').number(true, 2, '.', ' ');
			
			if (withCounts && headers) {
				$('#chooserAll').find('[selectionscounts]').empty();
				$('#chooserArchive').find('[selectionscounts]').empty();
				$('[chooserdepartment]').find('[selectionscounts]').empty();
				
				
				if (headers['x-count-contracts-all']) {
					$('#chooserAll').find('[selectionscounts]').text(headers['x-count-contracts-all'] > 0 ? headers['x-count-contracts-all'] : '');
				} else {
					//$('#chooserAll').find('[selectionscounts]').empty();
				}
				
				if (headers['x-count-contracts-archive']) {
					$('#chooserArchive').find('[selectionscounts]').text(headers['x-count-contracts-archive'] > 0 ? headers['x-count-contracts-archive'] : '');
				} else {
					//$('#chooserArchive').find('[selectionscounts]').empty();
				}
				
				if (headers['x-count-contracts-departments']) {
					let depsCounts = JSON.parse(headers['x-count-contracts-departments']);
					$.each(depsCounts, function(depId, count) {
						$('[chooserdepartment="'+depId+'"]').find('[selectionscounts]').text(count > 0 ? count : '');
					});
				} else {
					//$('[chooserdepartment]').find('[selectionscounts]').empty();
				}	
			}
			
			if (callback && typeof callback == 'function') callback(currentCount);
		});
	}
	
	
	
	
	
	
	
	//---------- Получение данных по количеству записей в разделах
	async function getCounts(callback) {
		if (abortCtrlCounts instanceof AbortController) abortCtrlCounts.abort();
		
		abortCtrlCounts = new AbortController();
		const params = {};
		
		/*if (currentList > 0) {
			params['archive'] = 0;
			params['department_id'] = currentList;
			params['pivot'] = {
				department_id: currentList,
				show: 1,
				hide: 0
			};
		} else {
			params['archive'] = currentList == -1 ? 1 : (searchWithArchive ? null : 0);
		}*/
		
		params['search'] = search;
		params['selection'] = selection;
		
		const {data, error, status, headers, abort} = await axiosQuery('get', 'site/contracts/counts', params, 'json', abortCtrlCounts);
		
		if (error) {
			$.notify(error.message, 'error');
			return;
		}
		
		if (!data) return;
		
		
		$('#chooserAll').find('[selectionscounts]').empty();
		$('#chooserArchive').find('[selectionscounts]').empty();
		$('[chooserdepartment]').find('[selectionscounts]').empty();
		
		
		if (data['x-count-contracts-all']) {
			$('#chooserAll').find('[selectionscounts]').text(data['x-count-contracts-all'] > 0 ? data['x-count-contracts-all'] : '');
		} else {
			//$('#chooserAll').find('[selectionscounts]').empty();
		}
		
		if (data['x-count-contracts-archive']) {
			$('#chooserArchive').find('[selectionscounts]').text(data['x-count-contracts-archive'] > 0 ? data['x-count-contracts-archive'] : '');
		} else {
			//$('#chooserArchive').find('[selectionscounts]').empty();
		}
		
		if (data['x-count-contracts-departments']) {
			let depsCounts = JSON.parse(data['x-count-contracts-departments']);
			$.each(depsCounts, function(depId, count) {
				$('[chooserdepartment="'+depId+'"]').find('[selectionscounts]').text(count > 0 ? count : '');
			});
		} else {
			//$('[chooserdepartment]').find('[selectionscounts]').empty();
		}	
		
			
		if (callback && typeof callback == 'function') callback();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------------- Вспомогательные функции
	
	
	
	
	function removeContractsRows(target) {
		if (selectedContracts.items.length > 1) {
			$.each(selectedContracts.items, function(k, contractId) {
				$('#contractsList').find('[contractid="'+contractId+'"]').remove();
			});
		} else {
			$(target.selector).remove();
		}
	}
	
	
	function preg_quote (str, delimiter) {
		return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&')
	}
	
	
	let lastLoadCount = null;
	
	$.doScrollStart = (target) => {
		let localOffset = offset - limit * countShownLoadings;
		if (localOffset < 0) return;
		getList({
			append: 'prepend',
			offset: (localOffset < 0 ? 0 : localOffset),
			callback: function(currentCount) {
				if (!currentCount) return;
				$('#contractsList').blockTable('removeRowsAfter', (countShownLoadings * limit + (lastLoadCount || limit)), (lastLoadCount || limit));
				if (lastLoadCount) lastLoadCount = null;
				offset -= limit;
			}
		});
	}
	
	$.doScrollEnd = (target) => {
		if ($('#contractsList').children('[ddrtabletr]').length < limit) return;
		offset += limit;
		getList({
			append: 'append',
			callback: function(currentCount) {
				if (!currentCount) {
					offset -= limit;
					return;
				} 
				
				lastLoadCount = Math.min(limit, currentCount);
				
				$('#contractsList').blockTable('removeRowsBefore', (countShownLoadings * limit + lastLoadCount), limit);
			}
		});
	}
	
	
	

	
	
</script>