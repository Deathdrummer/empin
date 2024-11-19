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

		<div class="row mb2rem align-items-center flex-nowrap justify-content-start" id="tableContainer">
			<div class="col-auto flex-shrink-1">
				<div class="horisontal horisontal_hidescroll horisontal_nopadding" id="depsChooser">
					<div class="horisontal__track">
						<div class="horisontal__item">
							<x-chooser variant="neutral" group="normal" px="10">
								<x-chooser.item
									id="chooserAll"
									action="getListAction:0"
									class="pl3rem"
									contract-chooser
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
												contract-chooser
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
										contract-chooser
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
									contract-chooser
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
							
				</div>
			</div>
			<div class="col-auto">
				<div id="gencontractingCount" class="h100 d-flex align-items-center"></div>
			</div>
			<div class="col-auto">
				<x-button
					group="normal"
					variant="neutral-light"
					rounded="2px"
					px="10"
					action="cancelContractFilter"
					id="clearAllFiltersBtn222"
					style="border-radius: 10px;"
					class="hidden2"
					>Снять фильтры 
					<img src="{{asset('assets/images/cancel_icon.png')}}" class="w1rem-8px ml2px" style="vertical-align: text-top;" alt="">
				</x-button>
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
	
	const {calcSubcontracting, calcGencontracting, showSelections, contextMenu, contentSelection, selectionsList, getLastnameFromApplicant} = loadSectionScripts({section: 'contracts'});


	let abortCtrl,
		abortCtrlCounts,
		abortCtrlFilter,
		abortCtrlFilterDates,
		currentList 			= 0,
		sortField 				= ddrStore('site-contracts-sortfield') || 'id',
		sortOrder 				= ddrStore('site-contracts-sortorder') || 'desc',
		limit 					= {{$setting['contracts-per-page'] ?? 10}},
		percentNds 				= {{$setting['price-nds'] ?? 1}},
		countShownLoadings 		= {{$setting['count-shown-loadings'] ?? 2}},
		canCreateCheckbox 		= '{{Auth::guard('site')->user()->can('contract-create-checkbox::site')}}',
		canRemoveCheckbox 		= '{{Auth::guard('site')->user()->can('contract-remove-checkbox::site')}}',
		canEditCell 			= '{{Auth::guard('site')->user()->can('contract-can-edit-cell::site')}}',
		offset 					= 0,
		search 					= null,
		columnFilter 			= [], // поиск по значению из столбца
		selection 				= ref(null),
		editSelection 			= null,
		searchWithArchive 		= false,
		selectedContracts 		= {},
		loadedContractsIds 		= {},
		lastChoosedRow 			= ref(null),
		totalCount 				= null,
		sendMessStat 			= ref(false),
		hidelights				= ref(null);



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
			selectedContracts.items = items.filter((value, index, self) => self.indexOf(value) === index);
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
	
	
	
	
	
	
	//--------------------------------------------------------------------------------- Перемещение курсором мыши
	let isMouseDownStat = false;
	
	$('#depsChooser').on('mousedown', (e) => {
		isMouseDownStat = true;
	});
	
	$('#depsChooser').on('mouseup mouseleave', (e) => {
		isMouseDownStat = false;
	});
	
	$('#depsChooser').on('mousemove', (e) => {
		if (!isMouseDownStat) return;
		const container = $('#depsChooser')[0];
		const moveX = e.originalEvent.movementX;
		container.scrollLeft -= moveX;
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
					withCounts: search || selection.value,
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
			withCounts: search || selection.value,
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
		selectionsList(selection, editSelection, _clearCounts, getList);
	}













	//--------------------------------------------------------------------------------- отменить текущую подборку
	$.clearSelection = (btn) => {
		$('[selectionsbtn]').ddrInputs('disable');
		selection.value = null;
		editSelection = null;
		_clearCounts();
		getList({
			withCounts: search || selection.value,
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


					let selectAllChecksStat = false;
					$.selectAllChecks = () => {
						$('#contractColumnList').find('[contractcolumn]').ddrInputs('checked', !selectAllChecksStat ? true : false);
						selectAllChecksStat = !selectAllChecksStat;
					};



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
















	//---------------------------------------- Открыть окно с общей информацией (убрать отметку нового договора)
	$.openContractInfo = (tr, contractId) => {
		let isNew = $(tr).attr('isnew');
		if ($(event.target).hasAttr('noopen')) return;
		const {isShiftKey, isCtrlKey, isCommandKey, isAltKey, isOptionKey, noKeys, isActiveKey} = metaKeys(event);

		if (!noKeys) return;

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
					getCommonInfo(commonInfoData, commonInfoError, contractId, setHtml, dialog);
					wait(false);
				});
			} else {
				axiosQuery('get', 'site/contracts/common_info', {contract_id: contractId}).then(({data, error, status, headers}) => {
					getCommonInfo(data, error, contractId, setHtml, dialog);
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

	function getCommonInfo(data, error, contractId, setHtml, dialog) {
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


			// Вкладка "файлы"
			const {getFiles, removeFile} = $.ddrFiles({
				chooseOnClick: true,
				dropSelector: '#contractInfoDropFiles',
				chooseSelector: '#contractInfoChooseFiles',
				multiple: true,
				dragover(selector) {
					$(selector).addClass('commoninfo__dropfiles-dragged');
				},
				dragleave(selector) {
					$(selector).removeClass('commoninfo__dropfiles-dragged');
				},
				drop(selector) {
					$(selector).removeClass('commoninfo__dropfiles-dragged');
				},
				init({count}) {
					$('#contractInfoDropFiles').find('[commoninfoofiles]').setAttrib('hidden');

					for (let i = 0; i < count; i++) {
						let fileColHtml = '<div class="col">';
						fileColHtml += 	'<div class="commoninfofile" filecontainer filecontainer-blank>';
						fileColHtml += 		'<div class="commoninfofile__icon" imgreplacer notouch>';
						//fileCol += 			'<img src="" title="">';
						fileColHtml += 		'</div>';
						fileColHtml += 		'<div class="commoninfofile__title">';
						fileColHtml += 			'<small filenamereplacer></small>';
						fileColHtml += 		'</div>';
						fileColHtml += 		'<div class="commoninfofile__buttons">';
						fileColHtml += 			'<div class="commoninfofile__remove" commoninfofileremove disabled><i class="fa-solid fa-trash" title="Удалить файл"></i></div>';
						fileColHtml += 		'</div>';
						fileColHtml += 	'</div>';
						fileColHtml += '</div>';

						const fileColSelector = $(fileColHtml);


						$(fileColSelector).find('[imgreplacer]').ddrWait({
							iconHeight: '30px',
							bgColor: '#fff3',
						});

						$('#uploadedeFilesBlock').append(fileColSelector);
					}
				},
				preload({key, iter, error}) {
					const fileContiner = $('#uploadedeFilesBlock').find('[filecontainer-blank]').first();
					$(fileContiner).setAttrib('file-id', key);
					$(fileContiner).removeAttrib('filecontainer-blank');
				},
				async callback({file, name, ext, key, size, type, isImage, preview, error}, {done, index}) {
					if (error) {
						console.log(error);
						return false;
					}

					const fileContainer = $('#uploadedeFilesBlock').find(`[file-id="${key}"]`);

					let success = true,
					fileSize = (size / 1024 / 1024).toFixed(1);

					if (fileSize > 500) {
						$.notify('Размер файла превышает максимально допустимый!', 'error');
						success = false;
					}

					/*if ([].includes(ext)) {
						$.notify('Недопустимый формат файла!', 'error');
						success = false;
					}*/

					if (!success) {
						$(fileContainer).closest('.col').remove();
						setEmptylabel();
						return;
					}

					const {filename: filenameSys} = await uploadContractFile({
						file,
						filename: `${name}.${ext}`,
						is_image: isImage,
						size,
						contract_id: contractId,
						fileContainer
					});


					let imgSrc;
					if (isImage) imgSrc = await preview({width: 100});
					else imgSrc = await loadImage(`assets/images/filetypes/${ext}.png`, 'assets/images/filetypes/untiped.png');


					$(fileContainer).find('[imgreplacer]').html(`<img src="${imgSrc}" />`);
					$(fileContainer).find('[filenamereplacer]').text(`${name}.${ext}`);
					$(fileContainer).find('[commoninfofileremove]').setAttrib('commoninfofileremove', filenameSys);
					$(fileContainer).find('[commoninfofileremove]').removeAttrib('disabled');
					$(fileContainer).setAttrib('filecontainer', filenameSys);
					$(fileContainer).setAttrib('title', `${name}.${ext} (${fileSize}Мб)`);

					$(fileContainer).removeAttrib('file-id');

					if (done) {
						$.notify('Готово!');
					}
				},
				fail() {
					console.log('fail');
				}
			});


			// Скачать файл
			$('#uploadedeFilesBlock').on(tapEvent, '[filecontainer]:not([filecontainer-blank]):not([disabled])', function(e) {
				e.preventDefault();
				if (e.detail < 2 || e.detail > 2) return;

				const filenameSys = $(this).attr('filecontainer'),
					filenameOrig = $(this).find('[filenamereplacer]').text();

				downloadContractFile({
					filenameSys,
					filenameOrig,
					contractId
				}, e.currentTarget);
			});


			// Удалить файл
			$('#uploadedeFilesBlock').on(tapEvent, '[filecontainer]:not([filecontainer-blank]):not([disabled]) [commoninfofileremove]', function(e) {
				const selector = this,
					filename = 'Название файла';
				dialog(`Удалить файл ${filename}?`, {
					buttons: {
						'Отмена|light': ({closeDialog}) => {
							closeDialog();
						},
						'Удалить|red': async ({closeDialog}) => {
							await removeContractFile(selector, contractId);
							setEmptylabel();
							closeDialog();
						}
					}
				});
			});






			async function uploadContractFile({file = null, filename = null, size = null, is_image = null, contract_id = null, fileContainer}) {
				const formData = new FormData();

				formData.append('file', file, filename);
				formData.append('filename_orig', filename);
				formData.append('size', size);
				formData.append('is_image', is_image ? 1 : 0);
				formData.append('contract_id', contract_id);

				try {
					const {data} = await axios.post('/ajax/contracts_files', formData, {headers: {'Content-Type': 'multipart/form-data'}});
					return data;
				} catch(err) {
					console.log(err);
					$(fileContainer).closest('.col').remove();
					setEmptylabel();
					$.notify('Ошибка загрузки файла!', 'error');
					return false;
				}
			}




			async function downloadContractFile({filenameSys = null, filenameOrig = null, contractId = null}, target) {
				if (!filenameSys || !contractId) return;
				$(this).setAttrib('disabled');

				const {destroy} = $(target).ddrWait({
					iconHeight: '30px',
					bgColor: '#fff9',
				});

				$(target).setAttrib('disabled');

				const {data, error, status, headers} = await axiosQuery('get', '/ajax/contracts_files', {filename: filenameSys, contract_id: contractId}, 'blob');
				if (error) {
					$.notify('Не удалось загрузить данные! Возможно, не загружен файл шаблона.', 'error');
					console.log(error?.message, error?.errors);
					//wait(false);
					destroy();
					return;
				}

				if (!headers['x-export-filename']) {
					$.notify('Не удалось загрузить данные! Возможно, не загружен файл шаблона.', 'error');
					//wait(false);
					destroy();
					return;
				}

				$.ddrExport({
					data,
					headers,
					filename: filenameOrig /*headers['x-export-filename'] || headers['export-filename']*/
				}, () => {
					$(target).removeAttrib('disabled');
					destroy();
				});
			}




			async function removeContractFile(selector = null, contractId = null) {
				if (!selector || !contractId) return false;

				const sysFileName = $(selector).attr('commoninfofileremove'),
					fileCol = $(selector).closest('.col'),
					formData = new FormData();

				formData.append('filename_sys', sysFileName);
				formData.append('contract_id', contractId);
				formData.append('_method', 'delete');

				try {
					$(selector).setAttrib('disabled');
					const {data} = await axios.post('/ajax/contracts_files', formData);
					$(fileCol).remove();
					$.notify('Файл успешно удален!');

				} catch(err) {
					console.log(err);
					$.notify('Ошибка удаления файла!', 'error');
				}

				$(selector).removeAttrib('disabled');
			}


			function setEmptylabel() {
				if ($('#uploadedeFilesBlock').find('[filecontainer]').length == 0) {
					$('#contractInfoDropFiles').find('[commoninfoofiles]').removeAttrib('hidden');
				}
			}

		});
	}



	$.copyCommonInfoItem = ({target, closeOnScroll, onContextMenu, onCloseContextMenu, changeAttrData, buildTitle, setStyle}) => {
		const hasSelectedStr = !!getSelectionStr();

		if (!hasSelectedStr) return false;

		setStyle({
			mainMinHeight: '30px',
			mainZIndex: 1001
		});

		onContextMenu(() => {

		});

		closeOnScroll('#commonInfoBlock');

		return [{
			name: 'Копировать',
			visible: hasSelectedStr,
			sort: 1,
			async onClick() {
				copyStringToClipboard(getSelectionStr());
				$.notify('Скопировано!', {autoHideDelay: 2000});
			}
		}];
	}





	$.contractInfoTabAction = (btn, asActive, type) => {
		if (asActive) return;

		$(btn).closest('[ddrpopupdata]').find(`[commoninfo]`).removeClass('commoninfo__scrollblock-visible');
		$(btn).closest('[ddrpopupdata]').find(`[commoninfo="${type}"]`).addClass('commoninfo__scrollblock-visible');

		//console.log(btn, asActive, type);
	};





















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
			sendMessStat.value = false;

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
		let cell = type == 5 ? $(`[deptlights^="${contractId},${departmentId},${stepId}"]`) : $(input).closest('[ddrtabletd]');

		$(input).ddrInputs('addClass', 'notouch');
		let inputId = $(input).attr('id');
		if (oldInputId == inputId) clearTimeout(contractSetDataTOut);
		oldInputId = inputId;

		let cellWait, color, colorName;
		if (type == 5) {
			if ($(input).hasClass('lightsitem_active')) return;

			cellWait = $(cell).ddrWait({
				iconHeight: '40px',
				bgColor: '#ffffffbb',
			});

			$(input).closest('.row').find('.lightsitem.lightsitem_active').removeClass('lightsitem_active');
			$(input).addClass('lightsitem_active');
		}



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

				case 5: // светофор.
					value = parseFloat($(input).attr('colorid'));
					color = $(input).attr('color');
					colorName = $(input).attr('title');
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
				} else if (type == 5) {
					const colors = {1: 'yellow', 2: 'green', 3: 'red'};

					if ($(cell).find('[lightsitem]').length) {
						if (color) {
							$(cell).find('[lightsitem]').css('background-color', color);
							$(cell).find('[lightsitem]').attr('title', colorName);
						} else $(cell).find('[lightsitem]').remove();
					} else if (color) {
						$(cell).html(`<div class="border-all border-gray-300 border-radius-10px w2rem-5px h2rem-5px" style="background-color:${color};" lightsitem title="${colorName}"></div>`);
					}

					$(cell).setAttrib('color', value);

					cellWait.destroy();
					hidelights.value();
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
			buttons: [{action: 'setFolders', title: 'Выгрузить папку', id: 'contractFoldersBtn', variant: 'purple'}, 'Отмена', {action: 'contractStore', title: 'Создать', id: 'contractStoreBtn'}],
			disabledButtons: true, // при старте все кнопки кроме закрытия будут disabled
			closeByBackdrop: false, // Закрывать окно по фону либо только по [ddrpopupclose]
			winClass: 'ddrpopup_white'
		}).then(({popper, state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			wait();

			axiosQuery('get', 'ajax/contracts/create', {views: 'admin.section.contracts.render.contracts', newItemIndex: 0, guard: 'site'})
				.then(({data, error, status, headers}) => {
					if (error) {
						$.notify(error.message, 'error');
						console.log(error?.message, error.errors);
					}

					if (data) setHtml(data, () => {
						enableButtons('close');
						$('#selfPrice').number(true, 2, '.', ' ');
						$('#selfPriceNds').number(true, 2, '.', ' ');
						$('#genPrice').number(true, 2, '.', ' ');
						$('#genPriceNds').number(true, 2, '.', ' ');
						$('#subPrice').number(true, 2, '.', ' ');
						$('#subPriceNds').number(true, 2, '.', ' ');




						// --------------------------------------------------- Работа с НДС
						const contractingPercent = ref($('#contractingPercent').val());
						const genpercentVariant = headers['x-genpercent'] || 'gen';
						/*if (genpercentVariant == 'gen') selfPriceNds.calc();
						else if (genpercentVariant == 'self') genPriceNds.calc();*/


						const selfNds = $('#selfPrice').ddrCalc([{
							selector: '#selfPriceNds',
							method: 'nds',
							percent: percentNds,
							twoWay: true,
							stat: () => $('#autoCalcState').is(':checked') == false
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





						// проверяем титул и номер договора.
						// При нахождении вывести: в таких-то номерах объектов уже используется такой номер договора или титул
						let changeContractFieldTOut;
						$(popper).find('[name="contract"], [name="titul"]').on('input', function(e) {
							clearTimeout(changeContractFieldTOut);

							changeContractFieldTOut = setTimeout(async () => {
								const value = e.target.value,
									field = e.target.getAttribute('name');

								if (field == 'contract' && value.length < 6) return;

								const {data, error, status, headers} = await axiosQuery('get', 'ajax/contracts/check_exists_contracts', {field, value}, 'json');

								if (error) {
									$.notify(error.message, 'error');
									return;
								}

								if (data.length) {
									const infoMap = {contract: 'номером договора', titul: 'титулом'};

									//$.notify(`Договор(ы) ${data.join(', ')} с таким же ${infoMap[field]}!`, 'error');

									$(e.target).ddrInputs('error', `Договор(ы) <b class="color-red">${data.join(', ')}</b> с таким же ${infoMap[field]}!`);
								}
							}, 500);

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







			$.setFolders = (btn) => {
				let formSelector = $('#contractForm'),
					objectNumber = $(formSelector).find('[name="object_number"]').val() || '',
					localy = $(formSelector).find('[name="locality"]').val() || '',
					applicant = getLastnameFromApplicant($(formSelector).find('[name="applicant"]').val() || '');

				const fileName = (`[${objectNumber}] ${capitalFirstLetter(localy)} ${applicant?.toUpperCase()}`).trim().replace(/\s+/g, ' '),
					folders = {[fileName]: [
						'1 Договор',
						'2 РП + ТУ',
						'3 ССР',
						'4 Выполнение',
						'5 Счета на материалы',
						'6 Фотоотчет',
						'7 Исполнительная докуентация',
					]};

				buildFolders(folders, fileName);
			}





			$.contractStore = (btn) => {
				wait();
				//let form = new FormData(document.querySelector('#newContractForm'));

				$('#genPercentFormField').val($('#genPercent:visible').val() || $('#subPercent:visible').val());
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








	$('#contractsTable').on(tapEvent, '[buynumber]', function(event) {
		event.preventDefault();
		event.stopPropagation();
		const {isShiftKey, isCtrlKey, isAltKey, noKeys} = metaKeys(event);
		if (!isAltKey || event.detail < 2) return;

		const buyNum = $(event.target).attr('buynumber');

		if (/^[a-zA-Z0-9]+$/.test(buyNum) === false) {
			$.notify('Некооректный номер закупки!', 'info');
			return;
		}

		const siteUrl = `https://tender.lot-online.ru/etp/app/SearchLots/page#%7B%22query%22:%7B%22title%22:%22${buyNum}%22%7D,%20%22filter%22:%7B%22state%22:%5B%22ALL%22%5D%7D,%20%22sort%22:%7B%22placementDate%22:false%7D%7D`,
			{outerWidth: winW, outerHeight: winH} = window,
				winWidth = winW - 200,
				winHeight = winH - 100,
				posLeft = winWidth < 700 ? 0 : 100,
				posTop = winHeight < 600 ? 0 : 50,
				win = window.open(siteUrl, '_blank');

		win.resizeBy(winWidth < 700 ? winW : winWidth, winHeight < 600 ? winH : winHeight);
		//win.moveBy(posLeft < 0 ? 0 : posLeft, posTop < 0 ? 0 : posTop);
		win.moveBy(posLeft < 0 ? 0 : posLeft, posTop < 0 ? 0 : posTop);
	});






	let haSContextMenu = ref(false);
	//----------------------------------------------------------------------------------- Выделение договоров
	$('#contractsTable').on(tapEvent, '[contractid]', function(event) {
		const {type, target, currentTarget, detail, which} = event;
		const {isShiftKey, isCtrlKey, isAltKey, noKeys} = metaKeys(event);

		let row = currentTarget,
			contractId = $(row).attr('contractid'),
			isCommon = !!$(target).closest('[ddrtabletd]').hasAttr('commonlist') || false;


		if (haSContextMenu.value) {
			selectedContracts.clear();
			haSContextMenu.value = false;
			lastChoosedRow.value = null;
		}

		if (!isCommon) {
			$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
			lastChoosedRow.value = null;
			selectedContracts.clear();
			return;
		}

		if (isCtrlKey) {
			if ($('#contractsTable').find('[contractselected]').length == 0) {
				selectedContracts.clear();
			}

			if ($(row).hasAttr('contractselected')) {
				$(row).removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
				let lastSelected = selectedContracts.remove($(row).attr('contractid'));
				lastChoosedRow.value = row;
			} else {
				$(row).addClass('ddrtable__tr-selected').setAttrib('contractselected');
				lastChoosedRow.value = row;
				selectedContracts.append($(row).attr('contractid'));
			}
		}



		if (isShiftKey) {
			if (lastChoosedRow.value) {
				let lastChoosedContractId = $(lastChoosedRow.value).attr('contractid');
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
				lastChoosedRow.value = row;
				selectedContracts.add($(lastChoosedRow.value).attr('contractid'));
			}
		}



		if (noKeys) {
			$('#contractsTable').find('[contractselected]').removeClass('ddrtable__tr-selected').removeAttrib('contractselected');
			lastChoosedRow.value = null;
			selectedContracts.clear();
		}

		//console.log('Выделенные договоры:', selectedContracts.items);
		//console.log(type, target, currentTarget, ctrlKey, shiftKey, detail, which);
	});











	//----------------------------------------------------------------------------------- Контекстное меню
	contextMenu(
		haSContextMenu,
		selectedContracts,
		removeContractsRows,
		sendMessStat,
		lastChoosedRow,
		canEditCell,
		canCreateCheckbox,
		canRemoveCheckbox,
		getCounts);










	//----------------------------------------------------------------------------------- Копирование текста в списке договоров
	contentSelection();














	//----------------------------------------------------------------------------------------------------- Фильтрация по полю

	let filterByDateTooltip,
		filterBySummTooltip,
		filterByStepTooltip,
		dateFromPicker,
		dateToPicker,
		dateFromValue = {},
		dateToValue = {},
		columnDateFilter;


	$.contractFilterBy = async ({target, preload, closeOnScroll, onContextMenu, onCloseContextMenu, changeAttrData, buildTitle, setStyle}, column) => {
		if (abortCtrlFilter instanceof AbortController) abortCtrlFilter.abort();
		
		setStyle({
			'mainFontSize': '12px',
			'mainMinHeight': '30px',
		});


		preload({iconSize: '3rem'});

		onContextMenu(({setMaxHeight}) => {
			setMaxHeight('calc(100vh - 300px)');
		});
		
		abortCtrlFilter = new AbortController();
		const {data, error, status, headers, abort} = await axiosQuery('get', 'site/contracts/column_values', {column, currentList}, 'json', abortCtrlFilter);

		if (error) {
			$.notify('Ошибка загрузки данных', 'error');
			console.log(error?.message, error.errors);
		}
		
		const navData = [];
		$.each(data, (_, {id, name, title}) => {
			let itemTitle = name || title;
			
			let itemName;
			if (columnFilter.findIndex(item => item?.value == id && item?.column == column) !== -1) {
				itemName = '<span class="color-blue">'+itemTitle+'</span>';
			} else {
				itemName = itemTitle;
			}

			navData.push({
				name: itemName,
				onClick() {
					//console.log({column, id});
					const hasItem = columnFilter.findIndex(item => item.column == column && item.value == id) != -1;
					if (hasItem) {
						columnFilter = columnFilter.filter(item => item.column != column || (item.column == column && item.value != id));
					} else {
						columnFilter.push({
							column,
							value: id
						});
					}

					dateFromValue = {};
					dateToValue = {};

					getList({
						withCounts: search || selection.value,
						callback: function() {}
					});
				}
			});
		});



		onCloseContextMenu(() => {});

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
						'<div class="col">' +
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





	//----------------------------------------------------------------------------------------------------- Применить фильтр по дате
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

		columnFilter = columnFilter.filter(item => item.column != columnDateFilter);
		columnFilter.push({
			column: columnDateFilter,
			value: buildData.join('|'), //dateFromValueFormat+'|'+dateToValueFormat,
		})
		

		getList({
			withCounts: search || selection.value,
			callback: function() {}
		});

		filterByDateTooltip.destroy();
		columnDateFilter = null;
		dateFromPicker.remove()
		dateToPicker.remove();
	}
	
	
	
	
	
	
	
	
	
	
	
	
	

	//----------------------------------------------------------------------------------------------------- Фильтрация по суммам
	$.contractFilterBySumm = (column) => {
		if (filterBySummTooltip?.destroy != undefined) filterBySummTooltip.destroy();
		
		
		filterBySummTooltip = $(event.currentTarget).ddrTooltip({
			//cls: 'w44rem',
			placement: 'bottom',
			tag: 'noscroll',
			minWidth: '100px',
			minHeight: '50px',
			duration: [200, 200],
			trigger: 'click',
			wait: {
				iconHeight: '40px'
			},
			onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
				
				const initData = columnFilter.find(item => item.column == column)?.value?.split('|'),
					valueFrom = initData ? (initData[0] || '') : '',
					valueTo = initData ? (initData[1] || '') : '';
				
				const summHtml = '<div class="mt5px">' +
					'<div class="row row-cols-2 g-10">' +
						'<div class="col">' +
							'<strong class="form__label color-dark fz12px lh90 mb4px">Сумма от:</strong>' +
							'<div class="input small-input small-input-text w10rem"><input type="text" value="'+valueFrom+'" id="filterSummFrom" filtersumminput /></div>' +
						'</div>' +
						'<div class="col">' +
							'<strong class="form__label color-dark fz12px lh90 mb4px">Сумма до:</strong>' +
							'<div class="input small-input small-input-text w10rem"><input type="text" value="'+valueTo+'" id="filterSummTo" filtersumminput /></div>' +
						'</div>' +
					'</div>' +
					'<div class="d-flex justify-content-end mt5px">' +
						'<div class="button verysmall-button button-yellow">' +
							'<button inpgroup="verysmall" id="contractFilterBySummBtn" disabled>Применить</button>' +
						'</div>' +
					'</div>' +
				'</div>';

				await setData(summHtml);

				waitDetroy();
				
				const filterSummFromSelector = $(popper).find('#filterSummFrom'),
					filterSummToSelector = $(popper).find('#filterSummTo'),
					filterSummBtn = $(popper).find('#contractFilterBySummBtn');
				
				let filterSummFromVal, filterSummToVal;
				
				$(filterSummFromSelector).number(true, 2, '.', ' ');
				$(filterSummToSelector).number(true, 2, '.', ' ');
				
				$(popper).find('[filtersumminput]').on('input', (e) => {
					if (Number(e.target.value) === 0 && e.target.value === '.00') e.target.value = '';
					
					filterSummFromVal = filterSummFromSelector.val(),
					filterSummToVal = filterSummToSelector.val();
					
					const filterSummFromNum = Number(filterSummFromVal),
						filterSummToNum = Number(filterSummToVal);
					
					if (
						(filterSummFromNum >= 0 && filterSummToNum == '') ||
						(filterSummFromNum >= 0 && filterSummToNum > 0 && filterSummFromNum <= filterSummToNum)
					) $(filterSummBtn).removeAttrib('disabled');
					else $(filterSummBtn).setAttrib('disabled');
				});
				
				
				$(popper).find('[filtersumminput]').on('keypress', (e) => {
					if (e.keyCode == 13) {
						setFilterBySumm();
					}
				});
				
				
				$(popper).find('#contractFilterBySummBtn').on(tapEvent, setFilterBySumm);
				
				
				function setFilterBySumm() {
					columnFilter = columnFilter.filter(item => item.column != column);
					columnFilter.push({
						column,
						value: `${filterSummFromVal}|${filterSummToVal}`,
					})
					
					getList({
						withCounts: search || selection.value,
						callback: function() {}
					});
					
					filterBySummTooltip.destroy();
				}
			}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Фильтрация по этапам
	$.contractFilterByStep = (stepData) => {
		if (filterByStepTooltip?.destroy != undefined) filterByStepTooltip.destroy();
		
		filterByStepTooltip = $(event.currentTarget).ddrTooltip({
			//cls: 'w44rem',
			placement: 'bottom',
			tag: 'noscroll',
			minWidth: '100px',
			minHeight: '50px',
			duration: [200, 200],
			trigger: 'click',
			wait: {
				iconHeight: '40px'
			},
			onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {
				const parseData = stepData.split('|'),
					stepType = parseData[0],
					stepId = parseData[1];
				
				// Тип данных.
				// 1: Чекбокс
				// 2: Текст
				// 3: Список сотрудников
				// 4: Денежный формат
				// 5: Светофор
				
				let itemsHtml;			
				if (stepType == 1) {
					const isActiveChecked = columnFilter.findIndex(item => item.column === 'step' && item.value[0] == stepType && item.value[1] == stepId && item.value[2] == 1) !== -1,
						isActiveUnchecked = columnFilter.findIndex(item => item.column === 'step' && item.value[0] == stepType && item.value[1] == stepId && item.value[2] == 0) !== -1,
						isActiveNone = columnFilter.findIndex(item => item.column === 'step' && item.value[0] == stepType && item.value[1] == stepId && item.value[2] == -1) !== -1;
					
					itemsHtml = '<ul class="ddrlist" id="deptFilter">\
						<li class="ddrlist__item pointer'+(isActiveChecked ? ' color-blue' : '')+' color-blue-hovered" filtervalue="1"'+(isActiveChecked ? ' checked' : '')+'><i class="fa-solid fa-fw fa-square-check" hovered colored></i> Активный чекбокс</li>\
						<li class="ddrlist__item mt3px pointer'+(isActiveUnchecked ? ' color-blue' : '')+' color-blue-hovered" filtervalue="0"'+(isActiveUnchecked ? ' checked' : '')+'><i class="fa-regular fa-fw fa-square" hovered colored></i> Неактивный чекбокс</li>\
						<li class="ddrlist__item mt3px pointer'+(isActiveNone ? ' color-blue' : '')+' color-blue-hovered" filtervalue="-1"'+(isActiveNone ? ' checked' : '')+'><i class="fa-solid fa-fw fa-ban" hovered colored></i> Чекбокс отсутствует</li>\
					</ul>';
					
				} else if (stepType == 3) {
					const {data, error, status, headers, abort} = await axiosQuery('get', 'ajax/get_employee_list');
					
					itemsHtml = '<div class="scrollblock scrollblock-light" style="min-height: 40px; max-height: calc(100vh - 300px);"><ul class="ddrlist" id="deptFilter">';
					
					const isActiveNone = columnFilter.findIndex(item => item.column === 'step' && item.value[0] == stepType && item.value[1] == stepId && item.value[2] == -1) !== -1;
					itemsHtml += `<li class="ddrlist__item pointer ${isActiveNone ? ' color-blue' : ''} color-blue-hovered" filtervalue="-1"${isActiveNone ? ' checked' : ''}>Сотрудник не выбран</li>`;
					
					data.forEach(user => {
						const isActive = columnFilter.findIndex(item => item.column === 'step' && item.value[0] == stepType && item.value[1] == stepId && item.value[2] == user.id) !== -1;
					   	itemsHtml += `<li class="ddrlist__item pointer ${isActive ? ' color-blue' : ''} color-blue-hovered" filtervalue="${user.id}"${isActive ? ' checked' : ''}>${user.pseudoname}</li>`;
					});
					
					itemsHtml += '</ul></div>';
				}
				
				
				await setData(itemsHtml);
				
				waitDetroy();
				
				$(popper).find('#deptFilter').on(tapEvent, '[filtervalue]', function(e) {
					const stepValue = e.currentTarget.getAttribute('filtervalue'),
						isChecked = e.currentTarget.hasAttribute('checked');
					
					if (isChecked) {
						columnFilter = columnFilter.filter(item => item.column !== 'step' || !(item.value[0] == stepType && item.value[1] == stepId && item.value[2] == stepValue));
					} else {
						columnFilter.push({
							column: 'step',
							value: [Number(stepType), Number(stepId), Number(stepValue)],
						})
					}
					
					getList({
						withCounts: search || selection.value,
						callback: function() {}
					});
					
					filterByStepTooltip.destroy();
				});
				
				
			}
		});
	}
	
	
	
	
	
	
	
	
	
	
	
	

















	//----------------------------------------------------------------------------------------------------- Отменить фильтрацию
	$.cancelContractFilter = (event, column, ...stepData) => {
		if (!column) {
			columnFilter = [];
			dateFromValue = {};
			dateToValue = {};
			columnDateFilter = null;
		} else {
			event.stopPropagation();
			
			if (column == 'step') {
				const [stepType, stepId] = stepData;
				columnFilter = columnFilter.filter(item => item.column !== 'step' || !(item.value[0] == stepType && item.value[1] == stepId));
			} else {
				columnFilter = columnFilter.filter(item => item.column !== column);
			}
			
			
			delete(dateFromValue[column]);
			delete(dateToValue[column]);

			if (filterByDateTooltip?.remove) filterByDateTooltip.destroy();
			columnDateFilter = null;

			if (dateFromPicker?.remove) dateFromPicker.remove()
			if (dateToPicker?.remove) dateToPicker.remove();
		}
		
		$('#clearAllFiltersBtn').ddrInputs('disable');
		
		getList({
			withCounts: search || selection.value,
			callback: function() {}
		});
	}















	//----------------------------------------------------------------------------------------------------- Тултип "светофор"
	let lightssTooltip;
	$.lightsTooltip = (event) => {
		const {target} = event;
		const cell = $(target).closest('[ddrtabletd]');

		if ($(cell).hasAttr('tooltiped')) return;

		if (lightssTooltip?.destroy != undefined) lightssTooltip.destroy();

		$(cell).setAttrib('tooltiped');
		const attrData = $(cell).attr('deptlights');
		const color = $(cell).attr('color');
		//console.log(attrData);
		const [contractId = null, departmentId = null, stepId = null] = pregSplit(attrData);

		$('#contractsList').one('scroll', function() {
			// При скролле списка скрыть тултип комментариев
			if (lightssTooltip?.destroy != undefined) lightssTooltip.destroy();
		});


		lightssTooltip = $(cell).ddrTooltip({
			//cls: 'w44rem',
			placement: 'bottom',
			tag: 'noscroll noopen',
			offset: [0 -5],
			minWidth: '100px',
			maxWidth: '210px',
			minHeight: '30px',
			duration: [200, 200],
			trigger: 'click',
			wait: {
				iconHeight: '40px'
			},
			onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {

				const {data, error, status, headers} = await axiosQuery('get', 'site/contracts/cell_lights', {
					contract_id: contractId,
					department_id: departmentId,
					step_id: stepId,
					color,
				}, 'json');

				await setData(data);


				hidelights.value = destroy;

				waitDetroy();

			},
			onDestroy: function() {
				$(cell).removeAttrib('tooltiped');
			}
		});
	}




















	//----------------------------------------------------------------------------------------------------- Тултип "комментарии"
	/*let commentsTooltipTOut;
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
				minWidth: '200px',
				minHeight: '200px',
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

					await setData(data);

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
								if (comment) $(reference).append('<div class="trangled trangled-top-right"></div>');
								else $(reference).find('.trangled').remove();

								//$.notify('Комментарий успешно сохранен!');
								//$(this).ddrInputs('change');
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
	}*/















	//-------------------------------------------------- Отобразить тултип с подборками договора
	let showselectionsTOut, selectionsTooltip;
	$.showselections = (contractId) => {
		const {isAltKey} = metaKeys(event);
		if (haSContextMenu.value || isAltKey) return;
		const cell = event.target;
		clearTimeout(showselectionsTOut);
		$(cell).one('mouseleave', () => {
			clearTimeout(showselectionsTOut);
		});
		$('#contractsList').one('scroll', function() {
			// При скролле списка скрыть тултип с подборками
			if (selectionsTooltip?.destroy != undefined) selectionsTooltip.destroy();
		});
		showselectionsTOut = setTimeout(() => {
			if (haSContextMenu.value) return;
			selectionsTooltip = showSelections(cell, contractId, selectionsTooltip, selection);
		}, 250);
	}






















	//----------------------------------------------------------------------------------------------------- Получение записей


	function getList(settings) {
		if (abortCtrl instanceof AbortController) abortCtrl.abort();

		let {
			init,
			withCounts,
			canEditSelection,
			append,
			offset: localOffset,
			callback
		} = _.assign({
			init: false,
			withCounts: false,
			canEditSelection: null,
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
			lastChoosedRow.value = null;
			selectedContracts.clear();
			loadedContractsIds.clear();
			
			
			//$('#chooserAll').find('[selectionscounts]').empty();
			//$('#chooserArchive').find('[selectionscounts]').empty();
			//$('[chooserdepartment]').find('[selectionscounts]').empty();
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
		params['filter'] = columnFilter.length ? JSON.stringify(columnFilter) : null;
		params['selection'] = selection.value;
		params['edit_selection'] = editSelection;
		params['selected_contracts'] = selectedContracts.items;
		params['can_edit_selection'] = canEditSelection;
		
		
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
		
		
		if (!withCounts && !append && !selection.value && !search) {
			$('.chooser__item.searchcontractcounts').removeClass('searchcontractcounts');
		} else if (withCounts) {
			$('.chooser__item').addClass('searchcontractcounts');
		}
		

		abortCtrl = new AbortController();
		axiosQuery('get', 'site/contracts', params, 'text', abortCtrl).then(({data, error, status, headers, abort}) => {
			if (error) {
				$.notify(error.message, 'error');
				return;
			}

			const currentCount = headers ? headers['x-count-contracts-current'] : null;
			const contractIds = headers ? headers['x-contracts-ids'] : null;
			const gencontractingCount = headers ? headers['x-count-contracts-gencontracting'] : null;

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
				totalCount = getTotalCount(headers, currentList);

				if (search && gencontractingCount > 0) {
					const genCunt = '<strong class="ml5px iconed iconed-noempty border-rounded-8px border-all border-white bg-gray color-white w2rem-8px h2rem-2px fz13px lh100">'+gencontractingCount+'</strong>';
					$('#gencontractingCount').html(genCunt+'<p class="color-gray-500 fz14px ml5px"> в генподрядных договорах</p>');
				} else {
					$('#gencontractingCount').empty();
				}

				$('#contractsTable').html(data);
			}

			const showTotal = headers && headers['x-count-contracts-current'] && ((params['offset'] + params['limit'] >= totalCount) || (totalCount <= params['limit']));

			showTotalFn(showTotal, totalCount);


			if (selection.value) {
				$('#tableContainer').removeClass('mb2rem').addClass('mb4rem');
			} else {
				$('#tableContainer').removeClass('mb4rem').addClass('mb2rem');
			}


			if (init) $('#contractsCard').card('ready');
			else listWait.destroy();

			if (abort && listWait) listWait.destroy();

			if (search && currentCount) {
				let s = ddrSplit(search, '+');
				if (s) {
					if (!_.isArray(s)) s = [s];
					$.each(s, function(k, searchItem) {
						if (searchItem === '' || searchItem === null) return true;
						let findSubStr = $('#contractsTable').find('p:icontains("'+searchItem+'"), strong:icontains("'+searchItem+'")');
						if (findSubStr) {
							$.each(findSubStr, function(k, item) {
								$(item).html($(item).text().replace(new RegExp("(" + preg_quote(searchItem) + ")", 'gi'), '<span class="highlight">$1</span>'));
							});
						}
					});
				}
			}

			if (currentCount) $('[stepprice]').number(true, 2, '.', ' ');
			
			
			
			if (headers) {
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
			
			const uniqueFilterCols = new Set(columnFilter.map(item => item.column == 'step' ? item.column+item.value[1] : item.column));
			
			if (uniqueFilterCols.size > 1) {
				$('#clearAllFiltersBtn').ddrInputs('removeClass', 'hidden');
				$('#clearAllFiltersBtn').ddrInputs('enable');
			} else $('#clearAllFiltersBtn').ddrInputs('addClass', 'hidden');

			if (callback && typeof callback == 'function') callback(currentCount);
		});
	}









	function showTotalFn(showTotal = false, totalCount = '--') {
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
	}





	//---------- Получить общее количество договоров выбранной вкладки
	function getTotalCount(headers = {}, currentList = null) {
		if (!headers || _.isNull(currentList)) return false;
		if (currentList > 0) {
			if (headers['x-count-contracts-departments']) {
				let depsCounts = JSON.parse(headers['x-count-contracts-departments']);
				return depsCounts[currentList] || false;
			}
		} else if (currentList === -1) {
			return headers['x-count-contracts-archive'] || false;
		} else if (currentList === 0) {
			return headers['x-count-contracts-all'] || false;
		}
		return false;
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
		params['selection'] = selection.value;

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
				$('#contractsList').find('[contractid="'+contractId+'"]').addClass('removed');
				$('#contractsList').find('[contractid="'+contractId+'"]').removeAttrib('ondblclick');
				$('#contractsList').find('[contractid="'+contractId+'"]').removeAttrib('contextmenu');
			});
		} else {
			$(target.selector).addClass('removed');
			$(target.selector).removeAttrib('ondblclick');
			$(target.selector).removeAttrib('contextmenu');
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