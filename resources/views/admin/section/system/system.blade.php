<section>
	<x-settings>
		<x-card
			loading="{{__('ui.loading')}}"
			ready
			>
			<div class="ddrtabs">
				<div class="ddrtabs__nav">
					<ul class="ddrtabsnav" ddrtabsnav>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab1">Заголовки и названия</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab2">Настройки страниц</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab3">Размеры</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab4">Договор</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab5">Админ. панель</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab6">ИИ-ассистент</li>
						<li class="ddrtabsnav__item" ddrtabsitem="systemTab7">Чертежи</li>
						<li class="ddrtabsnav__item ddrtabsnav__item_active" ddrtabsitem="systemTab8">AutoCAD</li>
					</ul>
				</div>
				
				<div class="ddrtabs__content ddrtabscontent" ddrtabscontent>
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab1">
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
							{{-- action="updatePromptFile:'{{$prompt_file_name}}'" --}}
							{{-- :value="$prompt_file_data" --}}
							/>
						
						<div class="h3rem"></div>
							
						<p class="color-gray mb1rem">Файлы:</p>
						
						{{-- <p class="format">{{$answer}}</p> --}}
						
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
					
					
					
					
					<div class="ddrtabscontent__item" ddrtabscontentitem="systemTab7">
						<div class="ddrdrawing">
							<div class="ddrdrawing__toolbar">
								<div class="ddrdrawing__toolbar-section">
									<div class="ddrdrawing__toolbar-group">
										<button class="ddrdrawing__tool-btn" data-tool="rectangle" title="Прямоугольник">
											<i class="fa-regular fa-square"></i>
										</button>
									</div>
									
									<div class="ddrdrawing__toolbar-group">
										<button class="ddrdrawing__tool-btn" data-tool="undo" title="Отменить">
											<i class="fa-solid fa-undo"></i>
										</button>
										<button class="ddrdrawing__tool-btn" data-tool="redo" title="Повторить">
											<i class="fa-solid fa-redo"></i>
										</button>
									</div>
									
									<div class="ddrdrawing__toolbar-group">
										<button class="ddrdrawing__tool-btn" data-tool="zoom-in" title="Увеличить">
											<i class="fa-solid fa-search-plus"></i>
										</button>
										<button class="ddrdrawing__tool-btn" data-tool="zoom-out" title="Уменьшить">
											<i class="fa-solid fa-search-minus"></i>
										</button>
										<button class="ddrdrawing__tool-btn" data-tool="zoom-fit" title="По размеру">
											<i class="fa-solid fa-expand-arrows-alt"></i>
										</button>
									</div>
									
									<div class="ddrdrawing__toolbar-group">
										<button class="ddrdrawing__tool-btn" data-tool="save" title="Сохранить">
											<i class="fa-solid fa-save"></i>
										</button>
										<button class="ddrdrawing__tool-btn" data-tool="load" title="Загрузить">
											<i class="fa-solid fa-folder-open"></i>
										</button>
										<button class="ddrdrawing__tool-btn" data-tool="export" title="Экспорт">
											<i class="fa-solid fa-download"></i>
										</button>
									</div>
								</div>
							</div>
							
							<div class="ddrdrawing__canvas-container">
								<div id="ddrDrawingCanvas" class="ddrdrawing__canvas"></div>
							</div>
						</div>
					</div>
					
					
					
					
					<div class="ddrtabscontent__item ddrtabscontent__item_visible" ddrtabscontentitem="systemTab8">
						{{-- тут разметка для autoCAD --}}
						<div class="autocad-converter">
							<div class="autocad-converter__header">
								<h3 class="autocad-converter__title">Конвертер AutoCAD файлов</h3>
								<div class="autocad-converter__settings-hint">
									<p><strong>Настройка папки с файлами:</strong> Укажите путь к папке с CAD файлами в <a href="/contracts#settings" target="_blank">настройках договоров</a> в поле "Локальное расположение файлов чертежей"</p>
									<p><strong>Пример пути:</strong> <code>C:\Users\deathdrumer\Desktop\cad</code></p>
								</div>
							</div>

							<div class="autocad-converter__workspace">
								<!-- Левая колонка - список файлов -->
								<div class="autocad-converter__left-panel">
									<div class="autocad-converter__files" id="autocadFilesList">
										<div class="autocad-converter__files-loading" id="autocadFilesLoading">
											<i class="fa-solid fa-spinner fa-spin"></i>
											<span>Загрузка списка файлов...</span>
										</div>

										<div class="autocad-converter__files-content" id="autocadFilesContent" style="display: none;">
											<div class="autocad-converter__files-header">
												<h4>CAD файлы в папке:</h4>
												<div class="autocad-converter__files-actions">
													<x-buttons-group group="small">
														<x-button id="autocadRefreshFiles" variant="neutral">
															<i class="fa-solid fa-refresh"></i>
															Обновить
														</x-button>
													</x-buttons-group>
												</div>
											</div>
											<div class="autocad-converter__files-list" id="autocadFilesListContainer">
												<!-- Список файлов будет вставлен через JS -->
											</div>
										</div>

										<div class="autocad-converter__files-error" id="autocadFilesError" style="display: none;">
											<i class="fa-solid fa-exclamation-triangle"></i>
											<span id="autocadFilesErrorText">Ошибка загрузки файлов</span>
											<div class="autocad-converter__error-actions">
												<x-buttons-group group="small">
													<x-button id="autocadRetryFiles" variant="blue">Повторить</x-button>
													<x-button id="autocadShowUpload" variant="gray">Загрузить файл</x-button>
												</x-buttons-group>
											</div>
										</div>

										<div class="autocad-converter__upload-fallback" id="autocadUploadFallback" style="display: none;">
											<div class="autocad-converter__upload-area" id="autocadUploadArea">
												<div class="autocad-converter__upload-content">
													<i class="fa-solid fa-file-upload autocad-converter__upload-icon"></i>
													<p class="autocad-converter__upload-text">
														Перетащите DWG или DXF файл сюда или
														<button class="autocad-converter__browse-btn" id="autocadBrowseBtn">выберите файл</button>
													</p>
													<p class="autocad-converter__upload-hint">
														Поддерживаемые форматы: .dwg, .dxf | Максимальный размер: 100MB
													</p>
												</div>
												<input type="file" id="autocadFileInput" accept=".dwg,.dxf" style="display: none;">
											</div>
										</div>
									</div>
								</div>

								<!-- Правая колонка - результат конвертации -->
								<div class="autocad-converter__right-panel">
									<div class="autocad-converter__result-area">
										<div class="autocad-converter__placeholder" id="autocadPlaceholder">
											<i class="fa-solid fa-mouse-pointer"></i>
											<p>Выберите файл для конвертации</p>
											<span>Результат конвертации появится здесь</span>
										</div>

										<div class="autocad-converter__processing" id="autocadProcessing" style="display: none;">
											<div class="autocad-converter__processing-content">
												<i class="fa-solid fa-cogs fa-spin"></i>
												<h4>Обработка файла</h4>
												<div class="autocad-converter__progress">
													<div class="autocad-converter__progress-bar" id="autocadProgressBar"></div>
												</div>
												<p class="autocad-converter__progress-text" id="autocadProgressText">Обработка файла...</p>
											</div>
										</div>

										<div class="autocad-converter__result" id="autocadResult" style="display: none;">
											<div class="autocad-converter__result-header">
												<h4>Результат конвертации</h4>
												<div class="autocad-converter__result-actions">
													<x-buttons-group group="small">
														<x-button id="autocadViewJson" variant="neutral">
															<i class="fa-solid fa-eye"></i>
															JSON
														</x-button>
														<x-button id="autocadDownloadJson" variant="gray">
															<i class="fa-solid fa-download"></i>
															Скачать
														</x-button>
														<x-button id="autocadSendToAI" variant="green">
															<i class="fa-solid fa-robot"></i>
															В ИИ
														</x-button>
													</x-buttons-group>
												</div>
											</div>

											<div class="autocad-converter__stats">
												<h5>Статистика файла:</h5>
												<div class="autocad-converter__stats-grid" id="autocadStats">
													<!-- Статистика будет вставлена через JS -->
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="autocad-converter__json-preview" id="autocadJsonPreview" style="display: none;">
								<div class="autocad-converter__json-header">
									<h4>Предварительный просмотр JSON:</h4>
									<button class="autocad-converter__close-preview" id="autocadClosePreview">
										<i class="fa-solid fa-times"></i>
									</button>
								</div>
								<pre class="autocad-converter__json-content" id="autocadJsonContent"></pre>
							</div>
						</div>
					</div>
					
					
				</div>
			</div>
		</x-card>
	</x-settings>
</section>






<script type="module">

	const drawingInstance = ddrDrawing();
	drawingInstance.init();

	// AutoCAD Converter
	class AutoCADConverter {
		constructor() {
			this.initElements();
			this.bindEvents();
			this.currentData = null;
			this.files = [];
			this.activeFileItem = null;
			this.loadFiles();
		}

		initElements() {
			this.filesLoading = document.getElementById('autocadFilesLoading');
			this.filesContent = document.getElementById('autocadFilesContent');
			this.filesError = document.getElementById('autocadFilesError');
			this.filesErrorText = document.getElementById('autocadFilesErrorText');
			this.filesListContainer = document.getElementById('autocadFilesListContainer');
			this.refreshBtn = document.getElementById('autocadRefreshFiles');
			this.retryBtn = document.getElementById('autocadRetryFiles');
			this.showUploadBtn = document.getElementById('autocadShowUpload');
			this.uploadFallback = document.getElementById('autocadUploadFallback');
			this.uploadArea = document.getElementById('autocadUploadArea');
			this.fileInput = document.getElementById('autocadFileInput');
			this.browseBtn = document.getElementById('autocadBrowseBtn');
			this.placeholder = document.getElementById('autocadPlaceholder');
			this.processing = document.getElementById('autocadProcessing');
			this.progressBar = document.getElementById('autocadProgressBar');
			this.progressText = document.getElementById('autocadProgressText');
			this.result = document.getElementById('autocadResult');
			this.stats = document.getElementById('autocadStats');
			this.viewJsonBtn = document.getElementById('autocadViewJson');
			this.downloadJsonBtn = document.getElementById('autocadDownloadJson');
			this.sendToAIBtn = document.getElementById('autocadSendToAI');
			this.jsonPreview = document.getElementById('autocadJsonPreview');
			this.jsonContent = document.getElementById('autocadJsonContent');
			this.closePreviewBtn = document.getElementById('autocadClosePreview');
		}

		bindEvents() {
			this.refreshBtn?.addEventListener('click', () => this.loadFiles());
			this.retryBtn?.addEventListener('click', () => this.loadFiles());
			this.showUploadBtn?.addEventListener('click', () => this.showUploadFallback());
			this.viewJsonBtn?.addEventListener('click', () => this.showJsonPreview());
			this.downloadJsonBtn?.addEventListener('click', () => this.downloadJson());
			this.sendToAIBtn?.addEventListener('click', () => this.sendToAI());
			this.closePreviewBtn?.addEventListener('click', () => this.hideJsonPreview());

			// Upload fallback events
			this.uploadArea?.addEventListener('dragover', (e) => {
				e.preventDefault();
				this.uploadArea.classList.add('drag-over');
			});

			this.uploadArea?.addEventListener('dragleave', () => {
				this.uploadArea.classList.remove('drag-over');
			});

			this.uploadArea?.addEventListener('drop', (e) => {
				e.preventDefault();
				this.uploadArea.classList.remove('drag-over');
				const files = e.dataTransfer.files;
				if (files.length > 0) {
					this.handleUploadedFile(files[0]);
				}
			});

			this.uploadArea?.addEventListener('click', () => {
				this.fileInput.click();
			});

			this.browseBtn?.addEventListener('click', (e) => {
				e.stopPropagation();
				this.fileInput.click();
			});

			this.fileInput?.addEventListener('change', (e) => {
				if (e.target.files.length > 0) {
					this.handleUploadedFile(e.target.files[0]);
				}
			});

			// ESC to close preview
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.jsonPreview.style.display !== 'none') {
					this.hideJsonPreview();
				}
			});
		}

		async loadFiles() {
			this.showLoading();

			try {
				const response = await fetch('/ajax/autocad/files', {
					method: 'GET',
					headers: {
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
					}
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.message || 'Ошибка загрузки файлов');
				}

				this.files = result.files;
				this.showFiles();

			} catch (error) {
				console.error('Ошибка загрузки файлов:', error);
				this.showError(error.message);
			}
		}


		showLoading() {
			this.filesLoading.style.display = 'block';
			this.filesContent.style.display = 'none';
			this.filesError.style.display = 'none';
		}

		showFiles() {
			this.filesLoading.style.display = 'none';
			this.filesError.style.display = 'none';

			if (this.files.length === 0) {
				this.filesListContainer.innerHTML = '<div class="autocad-converter__no-files">Файлы не найдены</div>';
			} else {
				this.renderFilesList();
			}

			this.filesContent.style.display = 'block';
		}

		showError(message) {
			this.filesLoading.style.display = 'none';
			this.filesContent.style.display = 'none';
			this.filesErrorText.textContent = message;
			this.filesError.style.display = 'block';
		}

		renderFilesList() {
			const filesHtml = this.files.map(file => `
				<div class="autocad-converter__file-item" data-filename="${file.name}">
					<div class="autocad-converter__file-info">
						<div class="autocad-converter__file-name">
							<i class="fa-solid fa-file-${file.extension === 'dwg' ? 'code' : 'lines'} autocad-converter__file-icon"></i>
							<span class="autocad-converter__file-title">${file.basename}</span>
							<span class="autocad-converter__file-ext">.${file.extension}</span>
						</div>
						<div class="autocad-converter__file-meta">
							<span class="autocad-converter__file-size">${file.size_human}</span>
							<span class="autocad-converter__file-date">${new Date(file.modified).toLocaleDateString('ru-RU')}</span>
						</div>
					</div>
					<div class="autocad-converter__file-actions">
						<div class="small-button button-blue">
							<button class="noselect autocad-converter__convert-btn" data-filename="${file.name}" inpgroup="small">
								<i class="fa-solid fa-cogs"></i>
								Конвертировать
							</button>
						</div>
					</div>
				</div>
			`).join('');

			this.filesListContainer.innerHTML = filesHtml;

			// Привязываем события к кнопкам и элементам файлов
			this.filesListContainer.querySelectorAll('.autocad-converter__convert-btn').forEach(btn => {
				btn.addEventListener('click', (e) => {
					e.stopPropagation();
					const filename = e.target.closest('.autocad-converter__convert-btn').dataset.filename;
					this.setActiveFile(e.target.closest('.autocad-converter__file-item'));
					this.convertFile(filename);
				});
			});

			// Добавляем клик по элементу файла для выделения
			this.filesListContainer.querySelectorAll('.autocad-converter__file-item').forEach(item => {
				item.addEventListener('click', (e) => {
					this.setActiveFile(item);
				});
			});
		}

		setActiveFile(fileItem) {
			// Убираем выделение с предыдущего файла
			if (this.activeFileItem) {
				this.activeFileItem.classList.remove('autocad-converter__file-item--active');
			}

			// Выделяем новый файл
			this.activeFileItem = fileItem;
			if (fileItem) {
				fileItem.classList.add('autocad-converter__file-item--active');
			}
		}

		async convertFile(filename) {
			this.showProcessing();

			try {
				this.updateProgress(30, 'Подготовка к обработке...');

				const response = await fetch('/ajax/autocad/convert-local', {
					method: 'POST',
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
					},
					body: JSON.stringify({ filename })
				});

				this.updateProgress(80, 'Обработка файла...');

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.message || 'Ошибка обработки файла');
				}

				this.updateProgress(100, 'Обработка завершена!');

				setTimeout(() => {
					this.showResult(result);
				}, 500);

			} catch (error) {
				console.error('Ошибка обработки файла:', error);
				this.showProcessingError(error.message);
			}
		}

		showProcessing() {
			this.placeholder.style.display = 'none';
			this.result.style.display = 'none';
			this.processing.style.display = 'block';
			this.updateProgress(0, 'Начало обработки...');
		}

		updateProgress(percent, text) {
			this.progressBar.style.width = percent + '%';
			this.progressText.textContent = text;
		}

		showResult(result) {
			this.currentData = result;
			this.processing.style.display = 'none';
			this.placeholder.style.display = 'none';

			// Заполняем статистику
			this.renderStats(result.data);

			this.result.style.display = 'block';
		}

		renderStats(data) {
			const stats = data.stats || {};
			const statsHtml = `
				<div class="autocad-converter__stat-item">
					<strong>${stats.entitiesCount || 0}</strong>
					<span>Объектов на чертеже</span>
				</div>
				<div class="autocad-converter__stat-item">
					<strong>${stats.layersCount || 0}</strong>
					<span>Слоев</span>
				</div>
				<div class="autocad-converter__stat-item">
					<strong>${stats.version || 'Неизвестно'}</strong>
					<span>Версия DXF</span>
				</div>
				<div class="autocad-converter__stat-item">
					<strong>${stats.units || 'Не указано'}</strong>
					<span>Единицы измерения</span>
				</div>
			`;
			this.stats.innerHTML = statsHtml;
		}

		showProcessingError(message) {
			this.processing.style.display = 'none';
			this.placeholder.style.display = 'block';
			alert('Ошибка: ' + message);
		}

		showJsonPreview() {
			if (!this.currentData) return;

			const jsonString = JSON.stringify(this.currentData.data, null, 2);
			this.jsonContent.textContent = jsonString;
			this.jsonPreview.style.display = 'flex';
		}

		hideJsonPreview() {
			this.jsonPreview.style.display = 'none';
		}

		downloadJson() {
			if (!this.currentData) return;

			const jsonString = JSON.stringify(this.currentData.data, null, 2);
			const blob = new Blob([jsonString], { type: 'application/json' });
			const url = URL.createObjectURL(blob);

			const a = document.createElement('a');
			a.href = url;
			a.download = 'autocad-data.json';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		}

		sendToAI() {
			if (!this.currentData) return;

			alert('Функция отправки ИИ-ассистенту будет реализована позже');

			console.log('Данные для ИИ:', {
				stats: this.currentData.data.stats,
				entities: this.currentData.data.entities?.slice(0, 10)
			});
		}
	}

	// Инициализация AutoCAD конвертера
	const autocadConverter = new AutoCADConverter();



	
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

