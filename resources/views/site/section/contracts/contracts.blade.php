<section>
	<x-card
		id="contractsCard"
		class="noselect"
		loading
		>
		
		
		<div class="row mb2rem gx-30 align-items-center">
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
					id="selectionsClearBtn"
					group="normal"
					w="3rem"
					variant="red"
					action="clearSelection"
					title="Отменить подборку"
					tag="selectionsbtn"
					disabled
					hidden
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
			
			<div class="col-auto">
				@cando('sozdanie-dogovora:site')
					<x-button
						group="normal"
						variant="green"
						action="contractNew"
						>Новый договор</x-button>
				@endcando
			</div>
		</div>
		
		<div class="row mb2rem">
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
			
		<div id="contractsTable"></div>
		
	</x-card>
</section>





<script type="module">
	
	let abortCtrl,
		currentList = 0,
		sortField = ddrStore('site-contracts-sortfield') || 'id',
		sortOrder = ddrStore('site-contracts-sortorder') || 'desc',
		limit = {{$setting['contracts-per-page'] ?? 10}},
		countShownLoadings = {{$setting['count-shown-loadings'] ?? 2}},
		offset = 0,
		search = null,
		selection = null,
		editSelection = null,
		searchWithArchive = false;
		
	getList({init: true});
	
	// 1
	// 2 with tag
	// 3 with tag
	// 4 with --UPLOAD
	// 5 with --UPLOAD
	// 6 with --UPLOAD
	// 7 with --UPLOAD
	
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
		
		$('#clearSearch').ddrInputs('enable');
		
		searchContractsTOut = setTimeout(() => {
			search = $(field).val();
			_clearCounts();
			if (search == '') {
				search = null;
				getList({
					withCounts: search || selection,
					callback: function() {
						$('#clearSearch').ddrInputs('disable');
					}
				});
			} else {
				getList({
					withCounts: true
				});
			}
		}, 300);
	}
	
	
	$.clearContractsSearch = (btn) => {
		$('#contractsSearchField').val('');
		search = null;
		_clearCounts();
		getList({
			withCounts: search || selection,
			callback: function() {
			}
		});
		$(btn).ddrInputs('disable');
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
						statusesTooltip = $(btn).tooltip({
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
						_clearCounts();
						getList({
							withCounts: true,
							callback: function() {
								$('[selectionsbtn]').ddrInputs('enable');
								$('#selectionsClearBtn').removeAttrib('hidden');
							}
						});
					}
					
					
					
					
					
					
					$.selectionBuildToEdit = (btn, id) => {
						$('[selectionsbtn]').ddrInputs('disable');
						close();
						selection = id;
						editSelection = true;
						_clearCounts();
						getList({
							withCounts: true,
							callback: function() {
								$('[selectionsbtn]').ddrInputs('enable');
								$('#selectionsClearBtn').removeAttrib('hidden');
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
				$(btn).setAttrib('hidden');
				$('[selectionsbtn]').ddrInputs('enable');
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
					
				});
			}
			
		});
	}
	
	
	
			
	
	
	
	
	
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Убрать отметку нового договора и открыть окно с общей информацией
	$.openContractInfo = (tr, contractId) => {
		let isNew = $(tr).attr('isnew');
		
		ddrPopup({
			title: false, 
			width: 800,
			buttons: ['Закрыть'],
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
	
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------  Отправить договор в архив
	$.toArchiveContractAction = ({context}, contractId, objectNumber, title) => {

		let html = '';
		html += '<div>';
		html += '<p class="fz14px color-darkgray text-start">Номер объекта: <span class="color-black">'+objectNumber+'</span></p>';
		html += '<p class="fz14px color-darkgray text-start">Название/заявитель: <span class="color-black">'+title+'</span></p>';
		html += '<p class="fz18px color-red mt15px">Вы действительно хотите отправить договор в архив?</p>';
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
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------  Отправить договор в другой отдел
	/*$.sendContractAction = (row, contractId) => {
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
						if (rowsCount == 1) row.changeAttrData(6, '0');
					} else {
						$.notify('Ошибка! Договор не был отправлен!', 'error');
					}
					close();
				});
			}
		});
	}*/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	$.loadDepsToSub = ({setItems, setCallback}, context, contractId, foo, bar) => {
		//console.log('context', context);
		axiosQuery('get', 'site/contracts/departments?contract_id='+contractId, {}, 'json', abortCtrl)
		.then(({data, error, status, headers}) => {
			//console.log(data);
			let subData = data.map(function(item) {
				return {
					name: item.name,
					faIcon: 'fa-solid fa-angles-right',
					callback: setCallback('clickToSub', contractId, item.id)
				};
			});
			setItems(subData);
		}).catch((e) => {
			
		});
	}
	
	
	
	
	$.clickToSub = ({setItems}, context, contractId, departmentId) => {
		
		//console.log('context', context);
		
		/*axiosQuery('post', 'site/contracts/send', {contractId, departmentId}, 'json').then(({data, error, status, headers}) => {
			if (data) {
				$.notify('Договор успешно отправлен в '+departmentName+'!');
				if (rowsCount == 1) row.changeAttrData(6, '0');
			} else {
				$.notify('Ошибка! Договор не был отправлен!', 'error');
			}
			close();
		});*/
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------  Контекстное меню
	let sendMessStat;
	$.contractContextMenu = (
		{target, closeOnScroll},
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
		
		closeOnScroll('#contractsList');
		
		return [
			{
				name: 'Отправить в архив',
				faIcon: 'fa-solid fa-box-archive',
				visible: canToArchive && !isArchive,
				sort: 5,
				onClick: () => {
					
					let html = '';
					html += '<div>';
					html += '<p class="fz14px color-darkgray text-start">Номер объекта: <span class="color-black">'+objectNumber+'</span></p>';
					html += '<p class="fz14px color-darkgray text-start">Название/заявитель: <span class="color-black">'+title+'</span></p>';
					html += '<p class="fz18px color-red mt15px">Вы действительно хотите отправить договор в архив?</p>';
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
							axiosQuery('post', 'site/contracts/to_archive', {contractId}, 'json')
							.then(({data, error, status, headers}) => {
								if (data) {
									let params = {};
									if (selectionId || searched) params['withCounts'] = true;
									getList(params);
									$.notify('Договор успешно отправлен в архив!');
								} else {
									$.notify('Ошибка! Договор не был отправлен в архив!', 'error');
								}
								close();
							});
						}
					});
				},
			}, {
				name: 'Отправить в другой отдел',
				faIcon: 'fa-solid fa-angles-right',
				enable: !!hasDepsToSend && ((canSending && departmentId) || (canSendingAll && !departmentId)),
				hidden: isArchive,
				sort: 4,
				load: {
					url: 'site/contracts/departments?contract_id='+contractId,
					method: 'get',
					map: (item) => {
						return {
							name: item.name,
							faIcon: 'fa-solid fa-angles-right',
							visible: true,
							onClick(selector) {
								let departmentName = selector.text(),
									itemsCount = selector.items().length;
								
								let procNotif = processNotify('Отправка договора в другой отдел...');
								
								axiosQuery('post', 'site/contracts/send', {contractId, departmentId: item.id}, 'json')
								.then(({data, error, status, headers}) => {
									if (data) {
										//$.notify('Договор успешно отправлен в '+departmentName+'!');
										if (selectionId || searched) {
											let params = {};
											getList({withCounts: true});
										}
										
										procNotif.done({message: 'Договор успешно отправлен в '+departmentName+'!'});
										if (itemsCount == 0) target.changeAttrData(6, '0');
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
				name: 'Чат договора ['+messagesCount+']',
				faIcon: 'fa-solid fa-comments',
				visible: canChat,
				sort: 1,
				onClick() {
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
				}
			}, {
				name: 'Скрыть договор',
				faIcon: 'fa-solid fa-eye-slash',
				visible: canHiding && departmentId && !isArchive,
				onClick() {	
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
									let params = {};
									if (selectionId || searched) params['withCounts'] = true;
									getList(params);
									$.notify('Договор успешно скрыт!');
									//target.changeAttrData(9, '0');
								} else {
									$.notify('Ошибка! Договор не был скрыт!', 'error');
								}
								close();
							});
						}
					});
				}
			}, {
				name: 'Вернуть договор в работу',
				faIcon: 'fa-solid fa-arrow-rotate-left',
				visible: canReturnToWork && isArchive,
				onClick() {
					ddrPopup({
						width: 400, // ширина окна
						html: '<p class="fz18px color-green">Вы действительно хотите вернуть договор в работу?</p>', // контент
						buttons: ['ui.cancel', {title: 'Вернуть', variant: 'green', action: 'returnContractToWorkBtn'}],
						centerMode: true,
						winClass: 'ddrpopup_dialog'
					}).then(({close, wait}) => {
						$.returnContractToWorkBtn = (_) => {
							wait();
							axiosQuery('post', 'site/contracts/to_work', {contractId}, 'json').then(({data, error, status, headers}) => {
								if (data) {
									let params = {};
									if (selectionId || searched) params['withCounts'] = true;
									getList(params);
									$.notify('Договор успешно возвращен в работу!');
									//target.changeAttrData(15, '0');
								} else {
									$.notify('Ошибка! Договор не был возвращен в работу!', 'error');
								}
								close();
							});
						}
					});
				}
			}, {
				name: 'Удалить из подборки',
				faIcon: 'fa-solid fa-trash-can',
				visible: selectionId,
				onClick() {
					$('[selectionsbtn]').ddrInputs('disable');
					let procNotif = processNotify('Удаление договора из подборки...');
					
					axiosQuery('put', 'site/selections/remove_contract', {contractId, selectionId})
					.then(({data, error, status, headers}) => {
						if (error) {
							//$.notify('Ошибка удаления из подборки!', 'error');
							procNotif.error({message: 'Ошибка удаления договора из подборки!'});
							console.log(error?.message, error.errors);
						} else {
							//$.notify('Договор успешно удален из подборки!');
							procNotif.done({message: 'Договор успешно удален из подборки!'});
							//target.changeAttrData(7, '0');
							getList({
								withCounts: true,
								callback: function() {
									$('[selectionsbtn]').ddrInputs('enable');
								}
							});
						}
					});
					
				}
			}, {
				name: 'Добавить в подборку',
				faIcon: 'fa-solid fa-clipboard-check',
				sort: 2,
				load: {
					url: 'site/contracts/selections_to_choose?contract_id='+contractId,
					method: 'get',
					map: (item) => {
						return {
							name: item.title,
							//faIcon: 'fa-solid fa-clipboard-check',
							disable: !!item.choosed,
							onClick(selector) {
								let selectionId = item.id;
								
								let procNotif = processNotify('Добавление договора в подборку...');
								
								//$().ddrInputs('disable');
								axiosQuery('put', 'site/selections/add_contract', {contractId, selectionId})
								.then(({data, error, status, headers}) => {
									if (error) {
										//$.notify('Ошибка добавления в подборку!', 'error');
										procNotif.error({message: 'Ошибка добавления в подборку!'});
										console.log(error?.message, error.errors);
										//$(select).ddrInputs('error');
									} else {
										//$(select).find('option[value="'+selectionId+'"]').setAttrib('disabled');
										//$(select).ddrInputs('enable');
										//$.notify('Договор успешно добавлен в подборку!');
										procNotif.done({message: 'Договор успешно добавлен в подборку!'});
									}
								});
							}
						};
					}
				},
			}, {
				name: 'Создать новую подборку',
				faIcon: 'fa-solid fa-clipboard-check',
				sort: 3,
				onClick() {
					
					console.log('rool');
					
					//$('[selectionsbtn]').ddrInputs('disable');
					//let procNotif = processNotify('Удаление договора из подборки...');
					//
					//axiosQuery('put', 'site/selections/remove_contract', {contractId, selectionId})
					//.then(({data, error, status, headers}) => {
					//	if (error) {
					//		//$.notify('Ошибка удаления из подборки!', 'error');
					//		procNotif.error({message: 'Ошибка удаления договора из подборки!'});
					//		console.log(error?.message, error.errors);
					//	} else {
					//		//$.notify('Договор успешно удален из подборки!');
					//		procNotif.done({message: 'Договор успешно удален из подборки!'});
					//		target.changeAttrData(7, '0');
					//		getList({
					//			withCounts: true,
					//			callback: function() {
					//				$('[selectionsbtn]').ddrInputs('enable');
					//			}
					//		});
					//	}
					//});
					
				}
			}
			
		];
		
		
		
		// removeFromSelection -> removeContractFromSelection:contractId,selectionId (Удалить из подборки)
		// addToSelection -> addContractToSelection:contractId (добавьть договор в подборку)
		// hide -> hideContractAction:contractId,departmentId (Скрыть)
		// sendToDepts -> sendContractAction:contractId (Отправить в другой отдел)(в отделе)
		// chatDept -> contractChatAction:contractId,title (Чат договора)
		// toArchive -> toarchivedata:objectNumber,title (Отправить в архив)
		// sendToDeptsAll -> sendContractAction:contractId (Отправить в другой отдел) (все договоры)
		// chat -> contractChatAction:contractId,title (Чат договора)
		// returnToWork -> returnContractToWorkAction:contractId (Вернуть договор в работу)
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-----------------------------------------------------------------------------------------------------
	
	
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
		} else {
			
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
		params['limit'] = limit;
		params['offset'] = localOffset != null ? localOffset : offset;
		params['append'] = append ? 1 : 0;
		params['search'] = search;
		params['selection'] = selection;
		params['edit_selection'] = editSelection;
		
		
		//-----------------------------------
		
		if (!init) {
			listWait = $('#contractsTable').ddrWait({
				iconHeight: '40px',
				text: 'Загрузка',
				fontSize: '14px',
				bgColor: '#ffffffbb',
				//position: 'adaptive'
			});
		} else {
		}
		
		
		abortCtrl = new AbortController();
		axiosQuery('get', 'site/contracts', params, 'text', abortCtrl).then(({data, error, status, headers, abort}) => {
			
			if (error) {
				$.notify(error.message, 'error');
				return;
			}
			
			const currentCount = headers ? headers['x-count-contracts-current'] : null;
			
			if (append) {
				if (currentCount) {
					if (append == 'prepend') {
						$('#contractsList').blockTable('prependData', data, limit);
					} else if (append == 'append') {
						$('#contractsList').blockTable('appendData', data);
					}
				}
				
			} else {
				$('#contractsTable').html(data);
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
			
			
			console.log(withCounts, headers);
			
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
	
	
	
	
	
	
	//-------------------------------------------------------------------------------------------
	
	
	
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
	
	// $.doScrollPart = (target) => {
	// 	
	// 	
	// 	
	// 	//console.log(target);
	// 	//offset += limit;
	// 	//console.log(offset);
	// 	//getList({append: true});
	// }
	
	
	
</script>