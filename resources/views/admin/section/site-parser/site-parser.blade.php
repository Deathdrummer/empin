<section>
	<x-card
		id="SitesDataCard"
		title="Список договоров"
		button="Импорт CSV данных"
		action="importSitesData"
		loading
		ready
		>
		
		<div class="row align-items-center mb2rem">
			<div class="col-auto mr-5rem">
				<x-chooser class="h24px" variant="neutral" px="20">
					<x-chooser.item action="getListAction:0" class="fz12px" active>Новые</x-chooser.item>
					<x-chooser.item action="getListAction:valid" class="fz12px">Утвержденные</x-chooser.item>
					<x-chooser.item action="getListAction:banned" class="fz12px">Отклоненные</x-chooser.item>
				</x-chooser>
			</div>
			
			<div class="col-auto"><p>Тематика:</p></div>
			<div class="col-auto">
				<x-button variant="red" group="small" action="clearSubjects" id="clearSubjectsBtn" disabled title="Очистить"><i class="fa-solid fa-fw fa-ban"></i></x-button>
				<x-button variant="blue" group="small" action="chooseSubject" title="Выбрать"><i class="fa-solid fa-fw fa-plus"></i></x-button>
			</div>
			<div class="col-auto fz12px" subjectstitles emptytext="Не выбрано"></div>
			<div class="col-auto ml-auto">
				<x-buttons-group group="small">
					<x-button variant="blue"><i class="fa-solid fa-fw fa-plus"></i></x-button>
				</x-input-group>
			</div>
		</div>
		
		
		<x-horisontal space="2rem" scroll="false" hidescroll="1" ignore="[noscroll], select, input, textarea" ignoremovekeys="alt, ctrl, shift">
			<x-horisontal.item class="h100">
				<x-table style="min-width: calc(100vw - 310px);" id="parsedListTable" noborder scrolled="{{'calc(100vh - 306px)'}}" scrollend="loadPart">
					<x-table.head noborder>
						<x-table.tr class="h4rem" scrollfix noborder>
							<x-table.td class="w-auto minw15rem sort" onclick="$.sortListByField(this, 'company')" noborder><strong class="fz12px">Название компании</strong></x-table.td>
							<x-table.td class="w20rem sort" onclick="$.sortListByField(this, 'site')" noborder><strong class="fz12px">Сайт</strong></x-table.td>
							<x-table.td class="w20rem sort" onclick="$.sortListByField(this, 'subject_id')" noborder><strong class="fz12px">Тематика</strong></x-table.td>
							<x-table.td class="w20rem sort" onclick="$.sortListByField(this, 'whatsapp')" noborder><strong class="fz12px">Whatsapp</strong></x-table.td>
							<x-table.td class="w20rem sort" onclick="$.sortListByField(this, 'telegram')" noborder><strong class="fz12px">Telegram</strong></x-table.td>
							<x-table.td class="w20rem sort" onclick="$.sortListByField(this, 'phone')" noborder><strong class="fz12px">Телефон</strong></x-table.td>
							<x-table.td class="w-auto sort" onclick="$.sortListByField(this, 'email')" noborder><strong class="fz12px">E-mail</strong></x-table.td>
							<x-table.td class="w12rem sort" noborder><strong class="fz12px">Действия</strong></x-table.td>
						</x-table.tr>
					</x-table.head>
					<x-table.body id="parsedList"></x-table.body>
				</x-table>
			</x-horisontal.item>
		</x-horisontal>	
	</x-card>
</section>





<script type="module">
	const subjectsOffset = ddrRef(''),
		subjectsLetter = ddrRef(null),
		countPerLoad = 50,
		loadListParams = ddrRef({
			stat: 0,
			subjectsIds: ddrStore('site-parser-subjects-ids') || [],
			sortField: 'id',
			sortOrder: 'desc',
			offset: 0,
			reload: false,
			endOfList: false,
		}, {
			set: ({prop, value}) => {
				if (prop == 'offset' && value > 0) {
					loadListData();
				}
				
				if (['stat', 'subjectsIds', 'sortField', 'reload'].includes(prop)) {
					if (prop == 'reload' && value == true) {
						loadListParams.reload = false;
					}
					
					if (prop == 'subjectsIds') {
						$('#clearSubjectsBtn').ddrInputs(value.length ? 'enable' : 'disable');
						ddrStore('site-parser-subjects-ids', value);
					}
					
					loadListParams.offset = 0;
					loadListParams.endOfList = false;
					
					loadListData();
				}
			}
		}),
		choosedSubjectsTitles = ddrRef('', {
			set: ({prop, value}) => {
				if (choosedSubjectsTitles.value.length) {
					
					let titlesHtml = '';
					
					titlesHtml += '<ul class="scrollblock scrollblock-hidescroll minh1rem maxh2rem-3px fz12px color-gray">';
					choosedSubjectsTitles.value.forEach((title) => {
						titlesHtml += `<li class="fz12px">${title}</li>`;
					});
					titlesHtml += '</ul>';
					
					$('[subjectstitles]').html(titlesHtml);
				} else {
					$('[subjectstitles]').empty();
				}	
			}
		});
	
	loadListData();
	
	
	$('#clearSubjectsBtn').ddrInputs(ddrStore('site-parser-subjects-ids')?.length ? 'enable' : 'disable');
	
	
	$.loadPart = (observer) => {
		loadListParams.offset += 1;
	}
	
	
	
	$.sortListByField = (item, field) => {
		const order = loadListParams.sortOrder == 'asc' ? 'desc' : 'asc';
		
		$(item).closest('[ddrtablehead]').find('[class*=sort-]').removeClass('sort-asc sort-desc');
		
		
		item.classList.add(`sort-${order}`);
		loadListParams.sortField = field;
		loadListParams.sortOrder = order;
	}
	
	
	
	
	$.chooseSubject = () => {
		ddrPopup({
			title: 'Выбор тематики ',
			width: 800,
			buttons: ['Закрыть', {action: 'unchooseSubjectAction', title: 'Снять выделение', disabled: 1, variant: 'yellow'}, {action: 'chooseSubjectAction', title: 'Выбрать', disabled: 1}],
			winClass: 'ddrpopup_white',
			closeByButton: true,
		}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onClose, onScroll, disableButtons, enableButtons, setWidth}) => {
			wait();
			
			const subjects = await getSubjects(loadListParams.subjectsIds, );
			
			if (subjects) {
				setHtml(subjects, () => {
					$('#subjectsTable').blockTable('buildTable');
					enableButtons(true);
				});
			} else {
				setHtml('<p class="color-gray fz14px text-center">Нет данных</p>');
				wait(false);
			}
			
			
			$.chooseSubjectAction = () => {
				wait();
				const choosedSubjects = [],
					choosedSubjectsTitlesData = [];
				$('#subjectsTable').find('[subject][selected]').each(function(k, item) {
					choosedSubjects.push(Number($(item).attr('subject')));
					choosedSubjectsTitlesData.push($(item).find('[subjecttitle]').text());
				});
				
				loadListParams.subjectsIds = choosedSubjects;
				choosedSubjectsTitles.value = choosedSubjectsTitlesData;
				close();
			}
			
			onClose(() => {
				subjectsOffset.value = 0;
			});
			
		});
	}
	
	
	
	
	
	
	
	$.unchooseSubjectAction = () => {
		$('#subjectsTable').find('[subject][selected]').removeAttrib('selected');
	}
	
	
	$.clearSubjects = () => {
		choosedSubjectsTitles.value = [];
		loadListParams.subjectsIds = [];
	}
	
	
	
	$.chooseSubjectLetter = async (item) => {
		disableButtons();
		$(item).closest('[subjectlettersblock]').find('[subjectletter][selected]').removeAttrib('selected');
		item.toggleAttribute('selected');
		const attr = $(item).attr('subjectletter');
		
		subjectsLetter.value = attr;
		subjectsOffset.value = 0;
		const subjects = await getSubjects(loadListParams.subjectsIds);
		
		if (subjects) {
			setHtml(subjects, () => {
				$('#subjectsTable').blockTable('buildTable');
				enableButtons(true);
			});
		} else {
			setHtml('<p class="color-gray fz14px text-center">Нет данных</p>');
			wait(false);
		}
	}
	
	
	
	$.scrollSubjects = async () => {
		subjectsOffset.value += 1;
		const subjects = await getSubjects(loadListParams.subjectsIds);
		$('#subjectsTable').blockTable('appendData', subjects);
	}
	
	
	
	
	
	
	$.getListAction = async (item, isActive, stat) => {
		if (isActive) return;
		loadListParams.stat = stat;
	}
	
	
	
	
	$.processContact = async (item, id, stat) => {
		$('#parsedList').find('[ddrtabletr].ddrtable__tr-selected').removeClass('ddrtable__tr-selected');	
		
		const {destroy} = $(item).closest('[ddrtabletr]').ddrWait({
			iconHeight: '36px',
			bgColor: '#ffffffbb',
		});
		
		
		const {data, error, status, headers} = await axiosQuery('post', 'ajax/siteparser/set_stat', {id, stat});
		
		if (error) {
			$.notify('Ошибка перемещения клиента!', 'error');
			console.log(error);
			destroy();
			return;
		}
		
		if (data) {
			$(item).closest('[ddrtabletr]').remove();
			$.notify('Клиент успешно перемещен!');
		}
		
	}
	
	
	
	$.openSite = (item, id, site) => {
		const {destroy} = $(item).closest('[ddrtabletr]').ddrWait({
			iconHeight: '36px',
			bgColor: '#ffffffbb',
		}),
			siteUrl = site.split('|')[0],
			{outerWidth: winW, outerHeight: winH} = window,
				winWidth = winW - 200,
				winHeight = winH - 100,
				posLeft = winWidth < 700 ? 0 : 100,
				posTop = winHeight < 600 ? 0 : 50,
				win = window.open(siteUrl, 'name', 'location=no,menubar=no,toolbar=no');
		
		$('#parsedList').find('[ddrtabletr].ddrtable__tr-selected').removeClass('ddrtable__tr-selected');	
		$(item).closest('[ddrtabletr]').addClass('ddrtable__tr-selected');
		
		win.resizeBy(winWidth < 700 ? winW : winWidth, winHeight < 600 ? winH : winHeight);
		//win.moveBy(posLeft < 0 ? 0 : posLeft, posTop < 0 ? 0 : posTop);
		win.moveBy(posLeft < 0 ? 0 : posLeft, posTop < 0 ? 0 : posTop);
		
		
		waitForClose(win, e => {
			destroy();
		});
		
	}
	
	
	
	
	
	
	
	//$("#SitesDataCard").card('ready');
	
	
	$.importSitesData = () => {
		
		ddrPopup({
			title: 'Импорт CSV данных',
			width: 600,
			buttons: ['Закрыть', {action: 'setImportSitesData', title: 'Загрузить', disabled: 1}],
			winClass: 'ddrpopup_white',
			closeByButton: true,
		}).then(async ({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => {
			wait();
			const {data, error, status, headers} = await axiosQuery('get', 'ajax/siteparser/import_form');
			
			let fileToParse;
			
			setHtml(data, () => {
				wait(false);
				enableButtons(true);
				
				const {on, off} = $('#importDataSelects').ddrWait({
					iconHeight: '36px',
					bgColor: '#ffffffbb',
				});
				
				off();
				
				
				$('#parserAddFile').on(tapEvent, function() {
					parserAddFile(wait, on, off);
				});
				
				
				$('#importDataInput').on('input', () => {
					on();
				});
				
				$('#importDataInput').ddrInputs('change', async (input) => {
					
					const titles = getTitles($(input).val());
					
					if (!titles) {
						off();
						$('[titlesselect]').html([setDisabledOption()]);
						$('[titlesselect]').ddrInputs('disable');
						$('[titlesselect]').ddrInputs('state', 'clear');
						return;
					}
					
					$('[titlesselect]').html(setOptions(titles));
					
					off();
					
					$('[titlesselect]').ddrInputs('enable');
					$('[titlesselect]').ddrInputs('state', 'clear');
				}, 100);
			});
			
				
			
			
			
			
			$.setImportSitesData = async () => {
				wait();
				
				const importdata = $('#importDataInput').val();
				
				const inputsData = $('#importPreserForm').ddrForm({fields: {importdata}});
				
				const formData = new FormData();
				
				
				for (const [field, value] of Object.entries(inputsData?.colums)) {
					formData.append(`colums[${field}]`, value);
				}
				
				for (const [field, value] of Object.entries(inputsData?.required)) {
					formData.append(`required[${field}]`, value);
				}
				
				formData.append('importdata', importdata);
				formData.append('importfile', fileToParse);
				
				
				const {data, error, status, headers} = await axiosQuery('post', 'ajax/siteparser/import_form', formData, 'text', null, {'Content-Type': 'multipart/form-data'});
				console.log({data, error, status, headers});
				if (error) {
					$.notify('Ошибка импорта данных!', 'error');
					console.log(error);
					wait(false);
					return;
				}
				
				if (data) {
					$.notify('Данные успешно импортированы!');
				} else {
					$.notify('Некоторые данные не были импортированы!', 'error');
				}
				
				close();
				loadListParams.reload = true;
			}
			
			
			
			function parserAddFile(wait, on, off) {
				$.ddrChooseFiles({
					//multiple,
					init(...data) {
						on();
					},
					//preload,
					async callback({file, name, ext, size, type, isImage, key}) {
						let success = true,
							fileSize = (size / 1024 / 1024).toFixed(2);
						
						if (fileSize > 100) {
							$.notify(`Размер файла превышает максимально допустимый в ${fileSize}мб!`, 'error');
							success = false;
						}
						
						if (ext != 'csv') {
							$.notify('Недопустимый формат файла!', 'error');
							success = false;
						}
						
						if (!success) return;
						
						fileToParse = file;
						
						const titles = getTitles(await readFile(fileToParse));
						if (!titles) {
							off();
							$('[titlesselect]').html([setDisabledOption()]);
							$('[titlesselect]').ddrInputs('disable');
							$('[titlesselect]').ddrInputs('state', 'clear');
							return;
						}
						
						$('[titlesselect]').html(setOptions(titles));
						
						off();
						
						$('[titlesselect]').ddrInputs('enable');
						$('[titlesselect]').ddrInputs('state', 'clear');
					},
					done() {
						
					},
					fail() {
						console.log('fail');
					}
				});
			}
			
		});	
	}
	
	
	
	
	
	
	
	//const {data, error, status, headers} = await axiosQuery('get', 'ajax/siteparser/get', 'json');
	
	//console.log(data);
	
	
	
	
	function getTitles(str = null) {
		if (!str) return false;
		
		const titlesStr = str.substr(0, str.indexOf("\n"));
		if (!titlesStr) return false;
		
		return titlesStr.split('|');
	}
	
	
	
	
	function setOptions(data = null, noSelect = 'Не выбрано', noSelectValue = '') {
		if (!data) return false;
		let options = [];
		
		const disabledOpt = new Option(noSelect, noSelectValue, true, true);
		options.push(disabledOpt);
		
		
		if (_.isArray(data)) {
			data.forEach((item, k) => {
				if (!item) return true;
				let opt = new Option(item || '-', k);
				options.push(opt);
			});
		} else if (_.isPlainObject(data)) {
			for (const [field, value] of Object.entries(data)) {
				let opt = new Option(value || '-', field);
				options.push(opt);
			}
		}
		
		
		return options;
	}
	
	
	
	function setDisabledOption() {
		const opt = new Option('Нет данных', '', true, true);
		opt.disabled = true;
		return opt;
	}
	
	
	
	
	
	async function readFile(file) {
		return new Promise(function(resolve, reject) {
			try {
				let reader = new FileReader();

			  reader.readAsText(file);

			  reader.onload = function() {
			    resolve(reader.result);
			  };

			  reader.onerror = function() {
			    console.log(reader.error);
			  };
			} catch(e) {
				reject(e);
				showError(e);
			}
		});
	}
	
	
	
	
	
	
	
	async function loadListData() {
		const {
			offset,
			subjectsIds,
			sortField,
			sortOrder,
			endOfList,
			stat,
		} = loadListParams;
		
		
		if (endOfList) {
			return;
		}
		
		const {destroy} = $('#parsedListTable').ddrWait({
			iconHeight: '36px',
			bgColor: '#ffffffbb',
		});
		
		const {data, error, status, headers} = await axiosQuery('get', 'ajax/siteparser/list', {offset, subjectsIds, sortField, sortOrder, stat});
		
		if (error) {
			$.notify('Ошибка загрузки данных!', 'error');
			console.log(error);
			destroy();
			return;
		}
		
		const rowsCount = headers['x-count-rows'];
		
		if (rowsCount < countPerLoad) {
			loadListParams.endOfList = true;

			if (offset > 0) $.notify('Конец списка!', 'info');
			
			if (rowsCount == 0) {
				if (offset == 0) $('#parsedList').blockTable('empty');
				destroy();
				return;
			} 
		}
		
		if (offset == 0) $('#parsedList').scrollTop(0);
		
		$('#parsedList').blockTable(offset == 0 ? 'insertData' : 'appendData', data);
		
		destroy();
	}
	
	
	
	
	
	
	async function getSubjects(choosedSubjects = null) {
		const {data, error, status, headers} = await axiosQuery('get', 'ajax/siteparser/get_subjects', {
			choosedSubjects,
			offset: subjectsOffset.value,
			letter: subjectsLetter.value,
		});
		
		if (error) {
			$.notify('Ошибка загрузки тематик!', 'error');
			console.log(error);
			return false;
		}
		
		return data;
	}
	
	
	
	function waitForClose(win, cb) {
		function poll() {
			if(win.closed) {
				cb();
			}
			else {
				requestAnimationFrame(poll);
			}
		}
		poll();
	}
	
	
</script>