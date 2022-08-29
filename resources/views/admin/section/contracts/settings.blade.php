<section>
	<x-settings>
		<div class="row row-cols-2 g-10">
			<div class="col">
				<x-card
					loading
					ready
					title="Общая информация"
					desc="Настройка полей общей информации договоров"
					>
					<x-simplelist
						setting="contracts-common-info"
						fieldset="Название поля:w30rem|text|name,Тип поля:w20rem|select|type,Количество строк:w10rem|number|rows_count"
						options="type;input:Однострочное поле,textarea:Многострочное поле"
						group="small"
					 />
				</x-card>
			</div>
		</div>
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
