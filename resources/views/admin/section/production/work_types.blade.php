<section>
	<x-card id="workTypesCard" loading>
		<p class="mb10px color-gray fz12px">
			Общий справочник видов работ ЭМПИН-pro: из него составляются планы объектов, задания и отчёты. Новые виды работ добавляются только здесь.
			Единицу измерения у вида работ, который уже есть в планах или заданиях, изменить нельзя — заведите новый вид работ.
			Удалённый вид работ остаётся в прежних планах и заданиях.
		</p>
		<div class="mb10px w40rem">
			<x-input id="workTypesSearch" group="normal" class="w100" placeholder="Поиск по наименованию" icon="magnifying-glass" />
		</div>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td><strong>Наименование</strong></td>
						<td class="w14rem"><strong>Ед. изм.</strong></td>
						<td class="w26rem"><strong>Где используется</strong></td>
						<td class="w14rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="workTypesList"></tbody>
				<tfoot>
					<tr>
						<td colspan="4" class="right">
							<x-button id="workTypeAddBtn" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>




<script type="module">

	const views = 'admin.section.production.render.work_types';

	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};


	// Поиск по списку: строки, где наименование не содержит текста, скрываются (новые строки не прячутся)
	$('#workTypesSearch').on('input', function() {
		const q = $(this).val().trim().toLowerCase();
		$('#workTypesList > tr').each(function() {
			const name = ($(this).find('[name="name"]').val() || '').toLowerCase();
			$(this).toggle(!q || $(this).is('[index]') || name.includes(q));
		});
	});


	$.ddrCRUD({
		container: '#workTypesList',
		itemToIndex: 'tr',
		route: 'ajax/work_types',
		viewsPath: views,
	}).then(({error, changeInputs, create, storeWithShow, update, destroy, remove}) => {

		$('#workTypesCard').card('ready');

		if (error) {
			$.notify(errorText(error), 'error');
			return false;
		}

		$('#workTypeAddBtn').ddrInputs('enable');

		changeInputs({'[save], [update]': 'enable'});


		$('#workTypeAddBtn').on(tapEvent, function() {
			let addWait = $(this).ddrWait({iconHeight: '26px', bgColor: '#ffffff91'});
			create((data, container, res) => {
				addWait.destroy();
				if (data) $(container).append(data);
				else $.notify(errorText(res?.error || container), 'error');
			});
		});


		$.workTypeSave = (btn) => {
			let row = $(btn).closest('tr');
			let saveWait = $(row).ddrWait({iconHeight: '26px', bgColor: '#ffffffd6'});
			storeWithShow(row, (data, container, res) => {
				saveWait.destroy();
				if (data) {
					$(row).replaceWith(data);
					$.notify('Вид работ добавлен');
					return;
				}
				const err = res?.error || container?.response?.data;
				$.notify(errorText(err || container), 'error');
				if (err?.errors) $.each(err.errors, (field, errors) => $(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]));
			});
		}


		$.workTypeRemoveNew = (btn) => remove($(btn).closest('tr'));


		$.workTypeUpdate = (btn) => {
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


		$.workTypeRemove = (btn, id) => {
			let row = $(btn).closest('tr');
			ddrPopup({
				width: 420,
				html: 'Удалить вид работ из справочника? В новые планы и задания он не попадёт, прежние планы и задания его сохранят.',
				buttons: ['ui.cancel', {title: 'Удалить', variant: 'red', action: 'workTypeRemoveAction'}],
				centerMode: true,
				winClass: 'ddrpopup_dialog color-red'
			}).then(({close, wait}) => {
				$.workTypeRemoveAction = () => {
					wait();
					destroy(id, (stat, container, res) => {
						if (stat) {
							remove(row);
							$.notify('Вид работ удалён из справочника');
						} else {
							$.notify(errorText(res?.error || container), 'error');
						}
						close();
					});
				}
			});
		}


		$.workTypeHistory = (btn, id) => {
			ddrPopup({
				title: 'История изменений вида работ',
				url: 'ajax/work_types/history',
				params: {work_type: id, views},
				width: 800,
				buttons: ['ui.close'],
			});
		}
	});

</script>
