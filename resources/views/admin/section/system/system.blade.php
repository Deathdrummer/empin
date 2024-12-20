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
				</div>
			</div>
		</x-card>
	</x-settings>
</section>






<script type="module">
	
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

