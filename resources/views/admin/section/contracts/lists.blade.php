<section>
	<x-settings>
		<div class="row row-cols-3 g-10">
			<div class="col">
				<x-card
					loading
					ready
					title="Заказчики"
					desc="Вспомогательные списки для работы с данными"
					>
					<x-simplelist
						setting="contract-customers"
						fieldset="ID:w7rem|number|id,Имя:w20rem|text|name"
						{{-- options="name_type;foo:fooval,bar:barval|name_radio;foo:rool,bar:tool" --}}
						onRemove="removeCustomerAction"
						group="small"
					 />
				</x-card>
			</div>
			
			<div class="col">
				<x-card
					loading
					ready
					title="Типы договоров"
					desc="Вспомогательные списки для работы с данными"
					>
					<x-simplelist
						setting="contract-types"
						fieldset="ID:w7rem|number|id,Название:w20rem|input|title"
						group="small"
					 />
				</x-card>
			</div>
			
			<div class="col">
				<x-card
					loading
					ready
					title="Исполнители"
					desc="Вспомогательные списки для работы с данными"
					>
					<x-simplelist
						setting="contract-contractors"
						fieldset="ID:w7rem|number|id,Имя:w20rem|input|name"
						group="small"
					 />
				</x-card>
			</div>
			
		</div>
	</x-settings>
	
		
	
	
	
	
	
	
	
	
	
	
	
	
		
	
	
	

	
	{{-- <div class="row g-10" hidden>
		<div class="col-4">
			<x-card
				loading
				ready
				title="Название"
				desc="писание"
				>
				<x-simplelist
					setting="simplelist"
					fieldset="Поле ввода:w20rem|input|name_title,Текстовое поле:w20rem|textarea|name_text,Выпадающий список:w20rem|select|name_type,Радио|radio|name_radio,Чекбокс|checkbox|name_checkbox"
					options="name_type;foo:fooval,bar:barval|name_radio;foo:rool,bar:tool"
					group="small"
				 />
			</x-card>
		</div>
		
		<div class="col-4">
			<x-card
				loading
				ready
				title="Название 2"
				desc="писание 2"
				>
				<x-simplelist
					setting="simplelist2"
					fieldset="Поле ввода:w20rem|input|name_title,Текстовое поле:w20rem|textarea|name_text,Выпадающий список:w20rem|select|name_type,Радио|radio|name_radio,Чекбокс|checkbox|name_checkbox"
					options="name_type;foo:fooval,bar:barval|name_radio;foo:rool,bar:tool"
					group="small"
				 />
			</x-card>
		</div>
		
		<div class="col-4">
			<x-card
				loading
				ready
				title="Название 2"
				desc="писание 2"
				>
				<x-input-group group="small">
					<div class="mb15px"><x-radio label="Радиокнапка 1" value="foo" setting="radio" /></div>
					<div class="mb15px"><x-radio label="Радиокнапка 2" value="bar" setting="radio" /></div>
					<div class="mb15px"><x-radio label="Радиокнапка 3" value="rool" setting="radio" /></div>
					<div class="mb15px"><x-radio label="Радиокнапка 4" value="well" setting="radio" /></div>
				</x-input-group>
			</x-card>
		</div>
	</div> --}}
	
	
</section>













<script type="module">
	
	$.removeCustomerAction = (tr, done) => {
		let customerId = $(tr).find('[field="id"]').val();
		axiosQuery('delete', 'ajax/steps_patterns/steps', {customer: customerId}, 'json').then(({data, error, status, headers}) => {
			if (error) {
				console.log(error?.message, error?.errors);
			}
			
			done();
		}).catch((e) => {
			console.log(e);
		});
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
