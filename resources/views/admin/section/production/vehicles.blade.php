<section>
	<x-card id="vehiclesCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w36rem"><strong>Наименование (тип)</strong></td>
						<td class="w18rem"><strong>Госномер</strong></td>
						<td><strong>Закреплённые водители</strong></td>
						<td class="w10rem center" title="Ремонт, продажа: в новых заданиях не предлагается"><strong>Не используется</strong></td>
						<td class="w14rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="vehiclesList"></tbody>
				<tfoot>
					<tr>
						<td colspan="5" class="right">
							<x-button id="vehicleAddBtn" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>




<script type="module">

	const views = 'admin.section.production.render.vehicles';

	// Текст отказа сервера: причина из правил справочника, первая ошибка проверки полей или общее сообщение
	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};

	const call = (route, data = {}) => axiosQuery('post', 'ajax/vehicles/'+route, data, 'json').then(({data, error}) => {
		if (error) throw error;
		return data;
	});


	$.ddrCRUD({
		container: '#vehiclesList',
		itemToIndex: 'tr',
		route: 'ajax/vehicles',
		viewsPath: views,
	}).then(({error, list, changeInputs, create, storeWithShow, update, destroy, remove}) => {

		$('#vehiclesCard').card('ready');

		if (error) {
			$.notify(errorText(error), 'error');
			return false;
		}

		$('#vehicleAddBtn').ddrInputs('enable');

		changeInputs({'[save], [update]': 'enable'});


		$('#vehicleAddBtn').on(tapEvent, function() {
			let addWait = $(this).ddrWait({iconHeight: '26px', bgColor: '#ffffff91'});
			create((data, container, res) => {
				addWait.destroy();
				if (data) $(container).append(data);
				else $.notify(errorText(res?.error || container), 'error');
			});
		});


		$.vehicleSave = (btn) => {
			let row = $(btn).closest('tr');
			let saveWait = $(row).ddrWait({iconHeight: '26px', bgColor: '#ffffffd6'});
			storeWithShow(row, (data, container, res) => {
				saveWait.destroy();
				if (data) {
					$(row).replaceWith(data);
					$.notify('Запись успешно сохранена!');
					return;
				}
				const err = res?.error || container?.response?.data;
				$.notify(errorText(err || container), 'error');
				if (err?.errors) $.each(err.errors, (field, errors) => $(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]));
			});
		}


		$.vehicleRemoveNew = (btn) => remove($(btn).closest('tr'));


		$.vehicleUpdate = (btn) => {
			let id = $(btn).attr('update'), row = $(btn).closest('tr');
			update(id, row, (data, container, res) => {
				if (data) {
					$(row).find('[update]').ddrInputs('disable');
					$(row).find('input, select, textarea').ddrInputs('state', 'clear');
					$.notify('Запись успешно обновлена!');
					return;
				}
				$.notify(errorText(res?.error || container), 'error');
			});
		}


		$.vehicleRemove = (btn, id) => {
			let row = $(btn).closest('tr');
			ddrPopup({
				width: 420,
				html: 'Удалить машину?',
				buttons: ['ui.cancel', {title: 'Удалить', variant: 'red', action: 'vehicleRemoveAction'}],
				centerMode: true,
				winClass: 'ddrpopup_dialog color-red'
			}).then(({close, wait}) => {
				$.vehicleRemoveAction = () => {
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


		$.vehicleHistory = (btn, id) => {
			ddrPopup({
				title: 'История изменений машины',
				url: 'ajax/vehicles/history',
				params: {vehicle: id, views},
				width: 900,
				buttons: ['ui.close'],
			});
		}


		// --- Окно «Водители машины»: галочка — закрепить, снять — открепить ---
		$.vehicleDrivers = (btn, id) => {
			let changed = false;
			const row = $(btn).closest('tr');
			const title = _.escape(row.find('[name="name"]').val()+' '+row.find('[name="plate"]').val());
			ddrPopup({
				title: 'Водители машины <span class="fz13px color-gray">'+title+'</span>',
				url: 'ajax/vehicles/drivers',
				params: {vehicle: id, views},
				width: 700,
				buttons: ['ui.close'],
			}).then(({onClose}) => {
				onClose(() => { if (changed) list({}); });

				$.vehicleDriverToggle = (checkbox, vehicleId, driverId) => {
					const assigned = $(checkbox).is(':checked') ? 1 : 0;
					call('set_driver', {vehicle: vehicleId, driver: driverId, assigned})
						.then(() => { changed = true; $.notify(assigned ? 'Водитель закреплён' : 'Водитель откреплён'); })
						.catch((e) => { $(checkbox).prop('checked', !assigned); $.notify(errorText(e), 'error'); });
				}
			});
		}
	});

</script>
