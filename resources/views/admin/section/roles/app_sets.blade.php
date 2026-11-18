<section>
	<x-card id="appSetsCard" loading>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w40rem"><strong>Название</strong></td>
						<td><strong>Права приложения</strong></td>
						<td class="w7rem center"><strong>Права</strong></td>
						<td class="w10rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="appSetsList"></tbody>
				<tfoot>
					<tr>
						<td colspan="4" class="right">
							<x-button id="appSetAddBtn" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>




<script type="module">

	const views = 'admin.section.roles.render.app_sets';

	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};


	$.ddrCRUD({
		container: '#appSetsList',
		itemToIndex: 'tr',
		route: 'ajax/app_sets',
		viewsPath: views,
	}).then(({error, list, changeInputs, create, storeWithShow, update, destroy, remove}) => {

		$('#appSetsCard').card('ready');

		if (error) {
			$.notify(errorText(error), 'error');
			return false;
		}

		$('#appSetAddBtn').ddrInputs('enable');

		changeInputs({'[save], [update]': 'enable'});


		$('#appSetAddBtn').on(tapEvent, function() {
			let addWait = $(this).ddrWait({iconHeight: '26px', bgColor: '#ffffff91'});
			create((data, container, res) => {
				addWait.destroy();
				if (data) $(container).append(data);
				else $.notify(errorText(res?.error || container), 'error');
			});
		});


		$.appSetSave = (btn) => {
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


		$.appSetRemoveNew = (btn) => remove($(btn).closest('tr'));


		$.appSetUpdate = (btn) => {
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


		$.appSetRemove = (btn, id) => {
			let row = $(btn).closest('tr');
			ddrPopup({
				width: 400,
				lhtml: 'dialog.delete',
				buttons: ['ui.cancel', {title: 'ui.delete', variant: 'red', action: 'appSetRemoveAction'}],
				centerMode: true,
				winClass: 'ddrpopup_dialog color-red'
			}).then(({close, wait}) => {
				$.appSetRemoveAction = () => {
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


		$.appSetRights = (btn, id) => {
			const row = $(btn).closest('tr');
			ddrPopup({
				url: 'ajax/app_sets/rights',
				params: {set: id},
				title: '<span class="fz13px color-gray">Права набора:</span> '+_.escape(row.find('[name="title"]').val()),
				width: 1000,
				buttons: ['ui.close'],
			}).then(({onClose}) => {
				onClose(() => list({}));
			});
		}
	});

</script>
