<section>
	<x-settings>
		<x-card
			loading="{{__('ui.loading')}}"
			ready
			>
			<div class="ddrtabs">
				<div class="ddrtabs__nav">
					<ul class="ddrtabsnav" ddrtabsnav>
						<li class="ddrtabsnav__item ddrtabsnav__item_active" ddrtabsitem="systemTab1">Заголовки и названия</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab2">Настройки страниц</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab3">Размеры</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab4">Договор</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab5">Админ. панель</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab6">ИИ-ассистент</li>
					</ul>
				</div>
				
				<div class="ddrtabs__content ddrtabscontent" ddrtabscontent>
					<div class="ddrtabscontent__item ddrtabscontent__item_visible" ddrtabscontentitem="systemTab1">
						<div class="row">
							<div class="col-auto">
								<x-input
									label="Название компании"
									group="large"
									setting="company_name"
									/>
							</div>
						</div>
					</div>
					
					
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab2">
						<div class="row row-cols-1 gy-20">
							<div class="col">
								<x-input
									class="w30rem"
									label="Стартовая страница"
									group="large"
									setting="site_start_page"
									/>
							</div>
							
							<div class="col">
								<x-input
									class="w30rem"
									label="Стартовая страница админ панели"
									group="large"
									setting="admin_start_page"
									/>
							</div>
							
							<div class="col">
								<x-checkbox
									label="Показывать главное меню в ЛК"
									group="large"
									setting="show_nav"
									/>
							</div>
						</div>
					</div>
					
					
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab3">
						<p class="color-gray mb1rem">Высота строки заголовков в списке договоров</p>
						<x-input
							label="rem"
							type="number"
							showrows
							group="large"
							setting="contract-list-titles-row-height"
							/>
					</div>
					
					
					
					
					
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab4">
						<p class="color-gray mb1rem">Порядковый номер обекта при создании договора</p>
						<x-input
							class="w16rem"
							type="number"
							showrows
							group="large"
							setting="last-contract-object-number"
							/>
						
						<div class="h3rem"></div>
						
						
						<p class="color-gray mb1rem">Ширина полей списка договоров</p>
						
						
						<div class="row gx-15 gy-20">
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Название / заявитель</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.title"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Заявитель</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.applicant"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Титул</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.titul"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Номер договора</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.contract"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Номер закупки</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.buy_number"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Заказчик</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.customer"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Населенный пункт</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.locality"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Стоим. без НДС</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.price"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Стоим. С НДС</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.price_nds"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Стоим. ген без НДС</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.price_gen"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Стоим. ген с НДС</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.price_gen_nds"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Стоим. суб без НДС</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.price_sub"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Стоим. суб с НДС</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.price_sub_nds"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Тип договора</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.type"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Исполнитель</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.contractor"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Архивная папка</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.archive_dir"
									/>
							</div>
							<div class="col-auto w14rem">
								<p class="fz10px mb4px">Количество актов КС-2</p>
								<x-input
									class="w100"
									type="number"
									showrows
									group="small"
									setting="contract-list-widths.count_ks_2"
									/>
							</div>
							
						</div>
						
						<div class="h3rem"></div>
						
						
						<div class="row">
							<div class="col-auto w18rem">
								<p class="color-gray mb1rem">Количество договоров на одну подгрузку</p>
								<x-input
									class="w16rem"
									type="number"
									showrows
									group="large"
									setting="contracts-per-page"
									/>
							</div>
							<div class="col-auto w18rem">
								<p class="color-gray mb1rem">Количество видимых подгрузок</p>
								<x-input
									class="w16rem"
									type="number"
									showrows
									group="large"
									setting="count-shown-loadings"
									/>
							</div>
						</div>
						
						
						
						<div class="h3rem"></div>
						
						
						<p class="color-gray mb1rem">НДС</p>
						<x-input
							class="w12rem"
							type="number"
							min="0"
							max="1000"
							showrows
							group="large"
							icon="percent"
							iconbg="yellow"
							inpclass="pr48px"
							setting="price-nds"
							/>
						
						
						<div class="h3rem"></div>
						
						
						<p class="color-gray mb1rem">При изменении Генподрядного процента менять:</p>
						<div class="row gx-30">
							<div class="col-auto">
								<x-radio group="normal" label="Стоимость своего договора" value="self" setting="contract-genpercent-change" />
							</div>
							<div class="col-auto">
								<x-radio group="normal" label="Стоимость Генподрядного договора" value="gen" setting="contract-genpercent-change" />
							</div>
						</div>
					</div>
					
					
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab5">
						<p class="color-gray mb1rem">Количество строк для вывода файлов договоров:</p>
						<x-input
							class="w12rem"
							type="number"
							min="10"
							max="500"
							showrows
							group="normal"
							setting="contract-files-part-count"
							/>
					</div>
					
					
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab6">
						<p class="color-gray mb1rem">Инструкция для ИИ-ассистента:</p>
						<x-textarea
							class="w100"
							rows="10"
							group="normal"
							action="updatePromptFile:'{{$prompt_file_name}}'"
							:value="$prompt_file_data"
							/>
						
						<div class="h3rem"></div>
							
						<p class="color-gray mb1rem">Файлы:</p>
						
						<p class="format">{{$answer}}</p>
						
						<div class="assistentfiles__scrollblock">
							<div class="assistentfiles__dropfiles" id="assistentDropFiles">
								<p class="color-light text-center assistentfiles__nofiles" notouch assistentfiles{{count($files) ? ' hidden' : ''}}>Нет файлов</p>
								<div class="row row-cols-10 gx-20 gy-40" id="uploadedeFilesBlock">
									@if(count($files))
										@foreach($files as $file)
											<div class="col">
												<div class="assistentfile" filecontainer="{{$file['filename_sys']}}" title="{{$file['filename_orig']}} ({{round($file['size'] / 1024 / 1024, 1, PHP_ROUND_HALF_EVEN)}}Мб)">
													<div class="assistentfile__icon" notouch>
														<img src="{{$file['thumb']}}" title="{{$file['filename_orig']}}">
													</div>
													<div class="assistentfile__title">
														<small filenamereplacer>{{$file['filename_orig']}}</small>
													</div>
													<div class="assistentfile__buttons">
														<div class="assistentfile__remove" assistentfileremove="{{$file['filename_sys']}}"><i class="fa-solid fa-trash" title="Удалить файл"></i></div>
													</div>
												</div>
											</div>
										@endforeach
									@endif
								</div>
							</div>
							
							<x-buttons-group group="normal" class="mb20px">
								<x-button variant="green" id="assistentChooseFiles">Загрузить файлы</x-button>
							</x-buttons-group>
						</div>
					</div>
				</div>
			</div>
		</x-card>
	</x-settings>
</section>






<script type="module">
	
	let savePromptTOut;
	$.updatePromptFile = (textarea, fileName) => {
		clearTimeout(savePromptTOut);
		savePromptTOut = setTimeout(async () => {
			const {data, error, status, headers} = await axiosQuery('put', '/ajax/prompt_files', {filename: fileName, content: textarea.value});
			
			if (error) {
				$.notify('Не удалось сохранить промпт!', 'error');
				console.log(error?.message, error?.errors);
				return;
			}
			
			if (data) {
				$(textarea).ddrInputs('state','clear');
			}
			
		}, 1000);
		
		
	}
	
	
	
	
	
	// Вкладка "файлы"
	const {getFiles, removeFile} = $.ddrFiles({
		chooseOnClick: true,
		dropSelector: '#assistentDropFiles',
		chooseSelector: '#assistentChooseFiles',
		multiple: true,
		dragover(selector) {
			$(selector).addClass('assistentfiles__dropfiles-dragged');
		},
		dragleave(selector) {
			$(selector).removeClass('assistentfiles__dropfiles-dragged');
		},
		drop(selector) {
			$(selector).removeClass('assistentfiles__dropfiles-dragged');
		},
		init({count}) {
			$('#assistentDropFiles').find('[assistentfiles]').setAttrib('hidden');

			for (let i = 0; i < count; i++) {
				let fileColHtml = '<div class="col">';
				fileColHtml += 	'<div class="assistentfile" filecontainer filecontainer-blank>';
				fileColHtml += 		'<div class="assistentfile__icon" imgreplacer notouch>';
				//fileCol += 			'<img src="" title="">';
				fileColHtml += 		'</div>';
				fileColHtml += 		'<div class="assistentfile__title">';
				fileColHtml += 			'<small filenamereplacer></small>';
				fileColHtml += 		'</div>';
				fileColHtml += 		'<div class="assistentfile__buttons">';
				fileColHtml += 			'<div class="assistentfile__remove" assistentfileremove disabled><i class="fa-solid fa-trash" title="Удалить файл"></i></div>';
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
				fileContainer
			});


			let imgSrc;
			if (isImage) imgSrc = await preview({width: 100});
			else imgSrc = await loadImage(`assets/images/filetypes/${ext}.png`, 'assets/images/filetypes/untiped.png');


			$(fileContainer).find('[imgreplacer]').html(`<img src="${imgSrc}" />`);
			$(fileContainer).find('[filenamereplacer]').text(`${name}.${ext}`);
			$(fileContainer).find('[assistentfileremove]').setAttrib('assistentfileremove', filenameSys);
			$(fileContainer).find('[assistentfileremove]').removeAttrib('disabled');
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
			filenameOrig
		}, e.currentTarget);
	});


	// Удалить файл
	$('#uploadedeFilesBlock').on(tapEvent, '[filecontainer]:not([filecontainer-blank]):not([disabled]) [assistentfileremove]', function(e) {
		const selector = this,
			filename = 'Название файла';
		
		ddrPopup({
			title: `Удалить файл ${filename}`,
			width: 400, // ширина окна
			html: `<p class="error center">Вы действительно хотите удалить файл ${filename}?</p>`, // контент
			buttons: ['ui.close', {action: 'deletefile', title: 'Удалить'}],
			buttonsAlign: 'center', // выравнивание вправо
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
			$.deletefile = async (btn) => {
				$(btn).ddrInputs('disable');
				await removeContractFile(selector);
				setEmptylabel();
				close();
			}
		});
	});
	
	
	
	
	async function uploadContractFile({file = null, filename = null, size = null, is_image = null, fileContainer}) {
		const formData = new FormData();

		formData.append('file', file, filename);
		formData.append('filename_orig', filename);
		formData.append('size', size);
		formData.append('is_image', is_image ? 1 : 0);

		try {
			const {data} = await axios.post('/ajax/assistent_files', formData, {headers: {'Content-Type': 'multipart/form-data'}});
			return data;
		} catch(err) {
			console.log(err);
			$(fileContainer).closest('.col').remove();
			setEmptylabel();
			$.notify('Ошибка загрузки файла!', 'error');
			return false;
		}
	}




	async function downloadContractFile({filenameSys = null, filenameOrig = null}, target) {
		if (!filenameSys) return;
		$(this).setAttrib('disabled');

		const {destroy} = $(target).ddrWait({
			iconHeight: '30px',
			bgColor: '#fff9',
		});

		$(target).setAttrib('disabled');

		const {data, error, status, headers} = await axiosQuery('get', '/ajax/assistent_files', {filename: filenameSys}, 'blob');
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




	async function removeContractFile(selector = null) {
		if (!selector) return false;

		const sysFileName = $(selector).attr('assistentfileremove'),
			fileCol = $(selector).closest('.col'),
			formData = new FormData();

		formData.append('filename_sys', sysFileName);
		formData.append('_method', 'delete');

		try {
			$(selector).setAttrib('disabled');
			const {data} = await axios.post('/ajax/assistent_files', formData);
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
			$('#assistentDropFiles').find('[assistentfiles]').removeAttrib('hidden');
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	$.openPopupWin = () => {
		ddrPopup({
			
			title: 'Тестовый заголовок',
			width: 400, // ширина окна
			html: '<p>Контентная часть</p>', // контент
			buttons: ['ui.close', {action: 'tesTest', title: 'Просто кнопка'}],
			buttonsAlign: 'center', // выравнивание вправо
			//disabledButtons, // при старте все кнопки кроме закрытия будут disabled
			//closeByBackdrop, // Закрывать окно только по кнопкам [ddrpopupclose]
			//changeWidthAnimationDuration, // ms
			//buttonsGroup, // группа для кнопок
			//winClass, // добавить класс к модальному окну
			//centerMode, // контент по центру
			//topClose // верхняя кнопка закрыть
		}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
						
		});
	}
	
	
	//$('button').ddrInputs('disable');
	
	
	/*$('#testRool').ddrInputs('error', 'error');
	$('#testSelect').ddrInputs('error', 'error');
	$('#testCheckbox').ddrInputs('error', 'error');
	
	
	$('#openPopup').on(tapEvent, function() {
		ddrPopup({
			title: 'auth.greetengs',
			lhtml: 'auth.agreement'
		}).then(({wait}) => {
			//wait();
		});
	});*/
</script>

