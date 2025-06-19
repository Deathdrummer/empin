<section>
	<x-card
		id="contractsFilesCard"
		title="Список файлов договоров"
		loading
		:buttons="[['type' => 'icon', 'icon' => 'filter', 'title' => 'Сбросить фильтр', 'id' => 'contractsFilesResetFilterBtn', 'group' => 'small', 'variant' => 'red', 'disabled' => 1], ['title' => 'Удалить выделенное', 'id' => 'contractsFilesRemoveBtn', 'group' => 'small', 'variant' => 'red', 'disabled' => 1]]"
		>
		
		<div class="mt2rem mb2rem">
			<div class="row align-items-center justify-content-between gx-20">
				<div class="col-auto">
					<x-input
						style="box-shadow: 0 0 8px 0 #0000000a;"
						id="contractsFilesSearchField"
						group="normal"
						type="search"
						class="w30rem"
						action="contractsFilesSearch"
						icon="magnifying-glass"
						{{-- iconaction="contractsSearch" --}}
						{{-- iconbg="light" --}}
						placeholder="Поиск по номеру объекта..."
						cleared
						/>
				</div>
				<div class="col-auto ml-auto">
					<p>Всего файлов: <strong totalcount emptytext="--"></strong> (<span class="fz12px" totalsize emptytext="--"></span>)</p>
				</div>
				<div class="col-auto">
					<p>Выборка: <strong totalfiltredcount emptytext="--"></strong> (<span class="fz12px" totalfiltredsize emptytext="--"></span>)</p>
				</div>
				<div class="col-auto">
					<p>Выделено: <strong selectdcount emptytext="0"></strong></p>
				</div>
			</div>

		</div>
		
		
		
		
		<x-table class="w100 mt2rem" id="contractsFilesTable" scrolled="calc(100vh - 335px)" scrollend="contractsFilesScroll" noborder>
			<x-table.head noborder>
				<x-table.tr noborder id="contractsFilesCols">
					<x-table.td class="w10rem sort sort-row" sort="filetype" oncontextmenu="$.contractsFilesFilter('filetype')"><strong class="color-red-hovered" noselect>Файл</strong></x-table.td>
					<x-table.td class="w30rem sort sort-row" sort="filename_orig"><strong noselect>Название файла</strong></x-table.td>
					<x-table.td class="w9rem sort sort-row" sort="size"><strong noselect>Размер файла</strong></x-table.td>
					
					<x-table.td class="w-auto"></x-table.td>
					
					<x-table.td class="w10rem sort sort-row" sort="contract_id.contracts.object_number" oncontextmenu="$.contractsFilesFilter('contract.object_number')"><strong noselect>№ объекта</strong></x-table.td>
					<x-table.td class="w16rem sort sort-row" sort="contract_id.contracts.applicant" oncontextmenu="$.contractsFilesFilter('contract.applicant')"><strong noselect>Заявитель</strong></x-table.td>
					<x-table.td class="w16rem sort sort-row" sort="from_id.users.pseudoname" oncontextmenu="$.contractsFilesFilter('author.pseudoname')"><strong noselect>Автор</strong></x-table.td>
					<x-table.td class="w13rem sort sort-row" sort="upload_date"><strong noselect>Дата загрузки</strong></x-table.td>
					<x-table.td class="w5rem sort sort-row" sort="contract_id.contracts.archive" oncontextmenu="$.contractsFilesFilter('contract.archive')"><strong noselect title="Состояние">Сост.</strong></x-table.td>
					<x-table.td class="w6rem center"><strong>Опции</strong></x-table.td>
				</x-table.tr>
			</x-table.head>
			<x-table.body class="minh-20rem" id="contractsFilesList"></x-table.body>
		</x-table>
			
			
			
		
		{{-- <div class="table mt2rem">
			<table>
				<thead>
					<tr id="contractsFilesCols">
						<x-table.td class="w7rem sort sort-row" sort="filetype" noselect oncontextmenu="$.contractsFilesFilter('filetype')">
							<strong>Тип файла</strong>
						</x-table.td>
						<x-table.td class="w30rem sort sort-row" sort="filename_orig" noselect><strong>Название файла</strong></x-table.td>
						<x-table.td class="w9rem sort sort-row" sort="size" noselect><strong>Размер файла</strong></x-table.td>
						
						<x-table.td class="w-auto"></x-table.td>
						
						<x-table.td class="w10rem sort sort-row" sort="contract_id.contracts.object_number" noselect><strong>№ объекта</strong></x-table.td>
						<x-table.td class="w16rem sort sort-row" sort="contract_id.contracts.applicant" noselect><strong>Заявитель</strong></x-table.td>
						<x-table.td class="w16rem sort sort-row" sort="from_id.users.pseudoname" noselect><strong>Автор</strong></x-table.td>
						<x-table.td class="w13rem sort sort-row" sort="upload_date" noselect><strong>Дата загрузки</strong></x-table.td>
						<x-table.td class="w8rem sort sort-row" sort="contract_id.contracts.archive" noselect><strong>Архивный</strong></x-table.td>
						<x-table.td class="w10rem center"><strong>Опции</strong></x-table.td>
					</tr>
				</thead>
				<tbody id="contractsFilesList"></tbody>
			</table>
		</div> --}}
		
	</x-card>
</section>




<script type="module">
	
	
	
	
	const lastChoosedRowSelector = ref(null);
	const listMeta = ref({
		totalCount: null,
		totalSize: null,
		totalFiltredCount: null,
		totalFiltredSize: null,
		itersCount: null
	});
	
	
	const getListParams = ddrRef({
		sort_field: 'id',
		sort_order: 'asc',
		offset: 0,
		filter: {},
		search: null,
	}, {set: ({target, prop, value, oldValue}) => {
		if (prop !== 'offset') {
			$('#contractsFilesList').scrollTop(0);
		}
		
		if (prop == 'sort_field' && (oldValue !== null && value !== oldValue)) {
			target.sort_order = 'asc';
		} else if (prop == 'sort_field' && oldValue !== null) {
			
			target.sort_order = target.sort_order == 'asc' ? 'desc' : 'asc';
		}
		
		if (prop != 'offset') target.offset = 0;
		
		if (prop == 'filter') {
			$('#contractsFilesResetFilterBtn').ddrInputs('enable');
		}
		
		
		getList();
	}});
	
	
	getList(true);
	
	
	$.contractsFilesScroll = (observer) => {
		if (getListParams.offset > listMeta.itersCount) return;
		getListParams.offset += 1;
	}
	
	
	
	
	
	
	
	$('#contractsFilesCols').on(tapEvent, '[sort]', function(e) {
		if (e.target.classList.contains('select')) return;
		
		const selector = e.currentTarget,
			otherSelectors = document.querySelectorAll('[sort]'),
			sortField = selector.getAttribute('sort');
		getListParams.sort_field = sortField;
		
		otherSelectors.forEach(selector => selector.classList.remove('sort-asc', 'sort-desc'));
		selector.classList.add(`sort-${getListParams.sort_order}`);
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//----------------------------------------------------------------------------------------------------- Фильтрация полей
	let filterTooltip, openrowmenuTooltip, columnDateFilter, abortCtrlFilterDates;
	$.contractsFilesFilter = (column) => {
		event.preventDefault();
		if (openrowmenuTooltip?.destroy != undefined) openrowmenuTooltip.destroy();
		if (filterTooltip?.destroy != undefined) filterTooltip.destroy();

		columnDateFilter = column;

		filterTooltip = $(event.currentTarget).ddrTooltip({
			//cls: 'w44rem',
			placement: 'bottom',
			tag: 'noscroll',
			minWidth: '150px',
			minHeight: '100px',
			duration: [200, 200],
			trigger: 'click',
			wait: {
				iconHeight: '40px'
			},
			onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {

				abortCtrlFilterDates = new AbortController();
				const {data, error, status, headers, abort} = await axiosQuery('get', 'ajax/get_values_to_filter', {column}, 'text', abortCtrlFilterDates);

				setData(data);
				waitDetroy();
				
				$(popper).on(tapEvent, '[filteritem]', function(e) {
					const selector = e.currentTarget,
					value = selector.getAttribute('filteritem');
					
					if (selector.hasAttribute('checked')) selector.removeAttribute('checked');
					else selector.setAttribute('checked', '');
					
					const countChoosed = $(popper).find('[filteritem][checked]').length;
					
					if (countChoosed > 0) {
						$('#contractsFilesSetFilter').ddrInputs('enable');
					} else {
						$('#contractsFilesSetFilter').ddrInputs('disable');
					}
				});
				
				
				
				$('#contractsFilesSetFilter').on(tapEvent, () => {
					const choosedItems = popper.querySelectorAll('[filteritem][checked]'),
						items = [];
					choosedItems.forEach((item) => {
						items.push(item.getAttribute('filteritem'));
					});
					
					getListParams.filter = JSON.stringify({items, column});
					
					hide();
				});
				
				
				
				
			}
		});
	}
	
	
	
	
	
	
	
	$.chooseFilterIterm = (column, value) => {
		console.log(column, value);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	$('#contractsFilesList').on(tapEvent, '[openrowmenu]', function(e) {
		if (filterTooltip?.destroy != undefined) filterTooltip.destroy();
		if (openrowmenuTooltip?.destroy != undefined) openrowmenuTooltip.destroy();
		
		const row = e.target.closest('[ddrtabletr]'),
			d = e.target.getAttribute('openrowmenu').split('|'),
			fileNameSys = d[0],
			fileNameOrig = d[1],
			contractId = Number(d[2]); 
		
		if (!fileNameSys || !contractId) {
			$.notify('Ошибка данных!', 'error');
			return;
		}
		
		openrowmenuTooltip = $(event.target).ddrTooltip({
			//cls: 'w44rem',
			placement: 'left-start',
			offset: [-5, 3],
			tag: 'noscroll',
			minWidth: '100px',
			minHeight: '30px',
			duration: [200, 200],
			trigger: 'click',
			wait: {
				iconHeight: '30px'
			},
			onShow: async function({reference, popper, show, hide, destroy, waitDetroy, setContent, setData, setProps}) {

				let content = '<ul>';
					
					content +=	'<li class="color-dark color-blue-hovered color-blue-active pointer" cfaction="download"><i class="fa-solid fa-download color-green fz16px"></i> Скачать</li>';
					content +=	'<li class="mt3px color-dark color-red-hovered color-blue-active pointer" cfaction="remove"><i class="fa-solid fa-trash color-red fz16px"></i> Удалить</li>';
					content += '</ul>';
				setData(content);
				waitDetroy();
				
				$(popper).find('[cfaction]').on(tapEvent, function(e) {
					const action = e.target.getAttribute('cfaction');
					let contractsFilesRowWait = $(row).ddrWait({
						iconHeight: '30px',
						bgColor: '#ffffffcc',
					});
					
					destroy();
					
					if (action == 'download') {
						downloadContractFile({
							fileNameSys,
							fileNameOrig,
							contractId
						}, () => {
							contractsFilesRowWait.destroy();
						});
						
					} else if (action == 'remove') {
						ddrPopup({
							title: 'Удалить файл',
							html: '<strong class="color-red fz14px">Вы действительно хотие удалить файл?</strong>',
							width: 400,
							buttons: ['Отмена', {action: 'removeContractFileAction', title: 'Удалить', variant: 'red'}],
							centerMode: true,
						}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => {
							
							$.removeContractFileAction = async (e) => {
								wait();
								const removeStat = await removeContractFiles(fileNameSys, contractId);
						
								if (removeStat) {
									$(row).remove();
								}
								
								setVisibleRemoveBtn();
								close();
							}
							
							onClose(() => {
								contractsFilesRowWait.destroy();
							});
						});
					}
					
				});
				
			}
		});
		
	});
	
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	let showRemoveBtnStat = false;
	// Скачать файл
	$('#contractsFilesList').on(tapEvent, '[filecontainer]:not([ddrwaiting])', function(e) {
		e.preventDefault();
		
		if (e.target.hasAttribute('noselectrow')) return;
		
		//if (e.detail < 2 || e.detail > 2) return;
		
		if ($(this).hasAttr('checked') === false) {
			if (e.ctrlKey !== true) $('#contractsFilesList').find('[filecontainer]').removeAttrib('checked').removeClass('ddrtable__tr-selected');
			$(this).setAttrib('checked');
			$(this).addClass('ddrtable__tr-selected');
		} else {
			const countChecked = $('#contractsFilesList').find('[filecontainer][checked]').length;
			
			if (e.ctrlKey === true) {
				$(this).removeAttrib('checked').removeClass('ddrtable__tr-selected');
			} else {
				$('#contractsFilesList').find('[filecontainer]').removeAttrib('checked').removeClass('ddrtable__tr-selected');
			}
			
			if (countChecked > 1 && e.ctrlKey !== true) {
				$(this).setAttrib('checked').addClass('ddrtable__tr-selected');
			}
		}
		
		
		if (e.shiftKey === true) {
			const prevIndex = $(lastChoosedRowSelector.value).index(),
				lastIndex = $(this).index(),
				[start, end] = [prevIndex, lastIndex].sort((x, y) => x - y);
			
			for (let i = start; i <= end; i++) {
				$('#contractsFilesList').find('[filecontainer]').eq(i).setAttrib('checked').addClass('ddrtable__tr-selected');
			}
		} else {
			lastChoosedRowSelector.value = this;
		}
		
		const countChecked = $('#contractsFilesList').find('[filecontainer][checked]').length;
		$('[selectdcount]').text(countChecked);
		
		setVisibleRemoveBtn();
	});


	// Удалить файл
	$('#contractsFilesList').on(tapEvent, '[commoninfofileremove]', async function(e) {
		e.stopPropagation();
		const selector = $(this).closest('tr'),
			d = $(this).attr('commoninfofileremove').split('|'),
			fileNameSys = d[0],
			contractId = d[1];
		
		ddrPopup({
			title: 'Удалить файл',
			html: '<strong class="color-red fz14px">Вы действительно хотие удалить файл?</strong>',
			width: 400,
			buttons: ['Отмена', {action: 'setContractsColums', title: 'Удалить', variant: 'red'}],
			centerMode: true,
		}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => {
			
			$.setContractsColums = async (e) => {
				wait();
				const removeStat = await removeContractFiles(fileNameSys, contractId);
		
				if (removeStat) {
					$(selector).remove();
				}
				
				setVisibleRemoveBtn();
				close();
			}
		});
		
		
	});
	
	
	
	// Удалить выделенные файлы
	$('#contractsFilesRemoveBtn').on(tapEvent, function(e) {
		ddrPopup({
			title: 'Удалить файлы',
			html: '<strong class="color-red fz14px">Вы действительно хотие удалить выделенные файлы?</strong>',
			width: 400,
			buttons: ['Отмена', {action: 'setContractsColums', title: 'Удалить', variant: 'red'}],
			centerMode: true,
		}).then(({state/* isClosed */, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => {
			
			$.setContractsColums = async (e) => {
				wait();
				
				const checkedFiles = $('#contractsFilesList').find('[filecontainer][checked]'),
					filesCols = [],
					filesData = [];
				
				
				for (let i = 0; i < checkedFiles.length; i++) {
	            	const item = checkedFiles[i];
	            	
	            	
					const fd = $(item).attr('filecontainer').split('|'),
						fileNameSys = fd[0] || null,
						contractId = Number(fd[1] )|| null;
					
					filesData.push({
						fileNameSys,
						contractId 
					});
					
					filesCols.push($(item)[0]);
		        }
				
				
				const removeStat = await removeContractFiles(filesData);
				
				//console.log(filesCols);
				
				if (removeStat) {
					$(filesCols).remove();
				}
				
				setVisibleRemoveBtn();
				close();
			}
		});
		
		
		
			
		
		
	});
	
	
	
	
	
	
	
	
	
	
	$('#contractsFilesResetFilterBtn').on(tapEvent, function(e) {
		getListParams.filter = {};
		$(e.target).ddrInputs('disable');
		
	});
	
	
	
	
	
	
	
	
	
	
	
	
	
	let filledSearchstat = false;
	$('#contractsFilesSearchField').on('input', function(e) {
		if (e.target.value && filledSearchstat === false)  {
			let replaceIconHtml = '<div class="postfix_icon bg-light bg-light-hovered pointer" onclick="$.clearContractsFilesSearch(this)"><i class="fa-solid fa-xmark"></i></div>';
			$('#contractsFilesSearchField').parent('.input').find('.postfix_icon').replaceWith(replaceIconHtml);
			filledSearchstat = true;
		} else if (!e.target.value && filledSearchstat === true) {
			let replaceIconHtml = '<div class="postfix_icon bg-light"><i class="fa-solid fa-magnifying-glass"></i></div>';
			$('#contractsFilesSearchField').parent('.input').find('.postfix_icon').replaceWith(replaceIconHtml);
			filledSearchstat = false;
			getListParams.search = null;
		}
	});
	
	
	$.clearContractsFilesSearch = (btn) => {
		$('#contractsFilesSearchField').val('');
		let replaceIconHtml = '<div class="postfix_icon bg-light"><i class="fa-solid fa-magnifying-glass"></i></div>';
		$('#contractsFilesSearchField').parent('.input').find('.postfix_icon').replaceWith(replaceIconHtml);
		filledSearchstat = false;
		getListParams.search = null;
	}
	
	
	$.contractsFilesSearch = _.debounce((btn) => {
		getListParams.search = $('#contractsFilesSearchField').val() || null;
	}, 500);
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------------------------------------------------------------------------------
	
	async function getList(init = false) {
		let contractsFilesListWait = $('#contractsFilesList').ddrWait({
			iconHeight: '40px',
			text: 'Загрузка',
			fontSize: '14px',
			bgColor: '#ffffffbb'
		});
		
		
		const {data, error, status, headers} = await axiosQuery('get', 'ajax/contracts_files_list', {
			...getListParams.all()
		});
		
		if (error) {
			$.notify('Ошибка вывода данных!', 'error');
			return;
		}
		
		if (getListParams.offset === 0) {
			$('#contractsFilesRemoveBtn').ddrInputs('disable');
			
			listMeta.totalCount = headers['x-count-items'] || null;
			listMeta.totalSize = headers['x-count-size-summ'] || null;
			
			listMeta.totalFiltredCount = headers['x-count-filtred-items'] || null;
			listMeta.totalFiltredSize = headers['x-count-filtred-size-summ'] || null;
			
			listMeta.itersCount = Math.floor(listMeta.totalCount / 10);
			
			$('[totalcount]').text(listMeta.totalCount);
			$('[totalsize]').text((listMeta.totalSize / 1024 / 1024).toFixed(1)+' Мб');
			$('[totalfiltredcount]').text(listMeta.totalFiltredCount);
			$('[totalfiltredsize]').text((listMeta.totalFiltredSize / 1024 / 1024).toFixed(1)+' Мб');
			$('[selectdcount]').text(0);
			
			if (data) $('#contractsFilesList').blockTable('insertData', data);
			else $('#contractsFilesList').blockTable('empty');
			
		} else {
			$('#contractsFilesList').blockTable('appendData', data);
		}
		
		
		setVisibleRemoveBtn();
		
		$('#contractsFilesCard').card('ready');
		
		contractsFilesListWait.destroy();
	}
	
	
	
	
	
	
	function removeContractFiles(sysFileName = null, contractId = null) {
		if (!sysFileName) return false;

		const formData = new FormData();
		
		if (_.isArray(sysFileName)) {
		    sysFileName.forEach((item, index) => {
		        formData.append(`files[${index}]`, JSON.stringify({fileNameSys: item.fileNameSys, contractId: item.contractId}));
		    });
		} else {
		    formData.append('filename_sys', sysFileName);
		    formData.append('contract_id', contractId);
		}
		
		formData.append('_method', 'delete');
		
		return new Promise(async function(resolve, reject) {
			try {
				const {data, error} = await axios.post('/ajax/contracts_files', formData);
				if (data && !error) resolve(true);
				else resolve(false);
			} catch(e) {
				reject(e);
			}
		});
	}
	
	
	
	
	
	
	
	async function downloadContractFile({fileNameSys = null, fileNameOrig = null, contractId = null}, cb) {
		console.log(123);
		if (!fileNameSys || !contractId) return;
		$(this).setAttrib('disabled');

		const {data, error, status, headers} = await axiosQuery('get', '/ajax/contracts_files', {filename: fileNameSys, contract_id: contractId}, 'blob');
		
		console.log({data, error, status, headers});
		
		if (error) {
			$.notify('Не удалось загрузить данные! Возможно, не загружен файл шаблона.', 'error');
			console.log(error?.message, error?.errors);
			//wait(false);
			callFunc(cb, false);
			return;
		}

		if (!headers['x-export-filename']) {
			$.notify('Не удалось загрузить данные! Возможно, не загружен файл шаблона.', 'error');
			//wait(false);
			callFunc(cb, false);
			return;
		}

		$.ddrExport({
			data,
			headers,
			filename: fileNameOrig /*headers['x-export-filename'] || headers['export-filename']*/
		}, () => {
			callFunc(cb, true);
		});
	}
	
	
	
	





	function setVisibleRemoveBtn() {
		const countChecked = $('#contractsFilesList').find('[filecontainer][checked]').length;
		
		if (countChecked > 0 && showRemoveBtnStat === false) {
			//console.log('show');
			$('#contractsFilesRemoveBtn').ddrInputs('enable');
			showRemoveBtnStat = true;
		} else if (countChecked == 0 && showRemoveBtnStat === true) {
			//console.log('hide');
			$('#contractsFilesRemoveBtn').ddrInputs('disable');
			showRemoveBtnStat = false;
		}
	}	
	
	
</script>