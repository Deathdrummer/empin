<section>
	<x-card id="driversCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w50rem"><strong>Водитель</strong></td>
						<td><strong>Закреплён за машинами</strong></td>
						<td class="w10rem center" title="Уволен, отстранён: в новых заданиях не предлагается"><strong>Не используется</strong></td>
						<td class="w14rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="driversList"></tbody>
				<tfoot>
					<tr>
						<td colspan="4" class="right">
							<x-button id="driverAddBtn" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>




<script type="module">

	const views = 'admin.section.production.render.drivers';

	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};


	$.ddrCRUD({
		container: '#driversList',
		itemToIndex: 'tr',
		route: 'ajax/drivers',
		viewsPath: views,
	}).then(({error, changeInputs, create, storeWithShow, update, destroy, remove}) => {

		$('#driversCard').card('ready');

		if (error) {
			$.notify(errorText(error), 'error');
			return false;
		}

		$('#driverAddBtn').ddrInputs('enable');

		changeInputs({'[save], [update]': 'enable'});


		$('#driverAddBtn').on(tapEvent, function() {
			let addWait = $(this).ddrWait({iconHeight: '26px', bgColor: '#ffffff91'});
			create((data, container, res) => {
				addWait.destroy();
				if (data) $(container).append(data);
				else $.notify(errorText(res?.error || container), 'error');
			});
		});


		$.driverSave = (btn) => {
			let row = $(btn).closest('tr');
			let saveWait = $(row).ddrWait({iconHeight: '26px', bgColor: '#ffffffd6'});
			storeWithShow(row, (data, container, res) => {
				saveWait.destroy();
				if (data) {
					$(row).replaceWith(data);
					$.notify('Запись успешно сохранена!');
					return;
				}
				$.notify(errorText(res?.error || container?.response?.data || container), 'error');
			});
		}


		$.driverRemoveNew = (btn) => remove($(btn).closest('tr'));


		$.driverUpdate = (btn) => {
			let id = $(btn).attr('update'), row = $(btn).closest('tr');
			update(id, row, (data, container, res) => {
				if (data) {
					$(row).find('[update]').ddrInputs('disable');
					$.notify('Запись успешно обновлена!');
					return;
				}
				$.notify(errorText(res?.error || container), 'error');
			});
		}


		$.driverRemove = (btn, id) => {
			let row = $(btn).closest('tr');
			ddrPopup({
				width: 420,
				html: 'Убрать из списка водителей?',
				buttons: ['ui.cancel', {title: 'Убрать', variant: 'red', action: 'driverRemoveAction'}],
				centerMode: true,
				winClass: 'ddrpopup_dialog color-red'
			}).then(({close, wait}) => {
				$.driverRemoveAction = () => {
					wait();
					destroy(id, (stat, container, res) => {
						if (stat) {
							remove(row);
							$.notify('Запись удалена');
						} else {
							$.notify(errorText(res?.error || container), 'error');
						}
						close();
					});
				}
			});
		}


		$.driverHistory = (btn, id) => {
			ddrPopup({
				title: 'История изменений водителя',
				url: 'ajax/drivers/history',
				params: {driver: id, views},
				width: 800,
				buttons: ['ui.close'],
			});
		}
	});

</script>
