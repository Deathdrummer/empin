<section>
	<x-card id="brigadesCard" loading>
		<p class="mb10px color-gray fz12px">
			Бригады ЭМПИН-pro: постоянный коллектив 2–5 человек вместе с бригадиром. Бригадиром можно быть только в одной бригаде,
			рядовым членом — в нескольких. Изменения сразу действуют в приложении. Бригада не удаляется, а расформировывается — история сохраняется.
		</p>
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td class="w40rem"><strong>Название бригады</strong></td>
						<td class="w40rem"><strong>Бригадир</strong></td>
						<td class="w16rem center"><strong>Состав</strong></td>
						<td><strong>Статус</strong></td>
						<td class="w14rem center"><strong>Действия</strong></td>
					</tr>
				</thead>
				<tbody id="brigadesList"></tbody>
				<tfoot>
					<tr>
						<td colspan="5" class="right">
							<x-button id="brigadeAddBtn" variant="blue" group="normal" px="15" disabled>Добавить</x-button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</x-card>
</section>




<script type="module">

	const views = 'admin.section.personal.render.brigades';

	// Текст отказа сервера: причина из правил бригад, первая ошибка проверки полей или общее сообщение
	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};

	const call = (route, data = {}) => axiosQuery('post', 'ajax/brigades/'+route, data, 'json').then(({data, error}) => {
		if (error) throw error;
		return data;
	});

	const dateInput = (name, value = '') => '<div class="input normal-input normal-input-date"><input type="date" name="'+name+'" value="'+value+'" inpgroup="normal"></div>';
	const today = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);


	$.ddrCRUD({
		container: '#brigadesList',
		itemToIndex: 'tr',
		route: 'ajax/brigades',
		viewsPath: views,
	}).then(({error, list, changeInputs, create, storeWithShow, update, remove}) => {

		$('#brigadesCard').card('ready');

		if (error) {
			$.notify(errorText(error), 'error');
			return false;
		}

		$('#brigadeAddBtn').ddrInputs('enable');

		changeInputs({'[save], [update]': 'enable'});

		const reloadList = () => list({});


		$('#brigadeAddBtn').on(tapEvent, function() {
			let addWait = $(this).ddrWait({iconHeight: '26px', bgColor: '#ffffff91'});
			create((data, container, res) => {
				addWait.destroy();
				if (data) $(container).append(data);
				else $.notify(errorText(res?.error || container), 'error');
			});
		});


		$.brigadeSave = (btn) => {
			let row = $(btn).closest('tr');
			let saveWait = $(row).ddrWait({iconHeight: '26px', bgColor: '#ffffffd6'});
			storeWithShow(row, (data, container, res) => {
				saveWait.destroy();
				if (data) {
					$(row).replaceWith(data);
					$.notify('Бригада создана');
					return;
				}
				const err = res?.error || container?.response?.data;
				$.notify(errorText(err || container), 'error');
				if (err?.errors) $.each(err.errors, (field, errors) => $(row).find('[name="'+field+'"]').ddrInputs('error', errors[0]));
			});
		}


		$.brigadeRemoveNew = (btn) => remove($(btn).closest('tr'));


		$.brigadeUpdate = (btn) => {
			let id = $(btn).attr('update'), row = $(btn).closest('tr');
			update(id, row, (data, container, res) => {
				if (data) {
					$(row).find('[update]').ddrInputs('disable');
					$(row).find('input, select, textarea').ddrInputs('state', 'clear');
					$.notify('Название сохранено');
					return;
				}
				$.notify(errorText(res?.error || container), 'error');
			});
		}


		$.brigadeDisband = (btn, id) => {
			ddrPopup({
				width: 420,
				title: 'Расформировать бригаду',
				html: '<p class="mb10px">С этой даты бригада перестаёт работать: новые задания ей не ставятся, прежние задания, отчёты и выходы сохраняются.</p>'+dateInput('disband_date', today()),
				buttons: ['ui.cancel', {title: 'Расформировать', variant: 'red', action: 'brigadeDisbandDo'}],
				winClass: 'ddrpopup_dialog',
			}).then(({close, wait, popper}) => {
				$.brigadeDisbandDo = () => {
					wait();
					call('disband', {brigade: id, date: $(popper).find('[name="disband_date"]').val()})
						.then((res) => {
							$.notify('Бригада расформирована');
							if (res?.tasks_left) $.notify('У бригады остались задания с этой даты: '+res.tasks_left+'. Их нужно передать другому исполнителю в приложении', 'error');
							close();
							reloadList();
						})
						.catch((e) => { wait(false); $.notify(errorText(e), 'error'); });
				}
			});
		}


		$.brigadeRestore = (btn, id) => {
			call('restore', {brigade: id})
				.then(() => { $.notify('Расформирование отменено'); reloadList(); })
				.catch((e) => $.notify(errorText(e), 'error'));
		}


		$.brigadeHistory = (btn, id) => {
			ddrPopup({
				title: 'История изменений бригады',
				url: 'ajax/brigades/history',
				params: {brigade: id, views},
				width: 900,
				buttons: ['ui.close'],
			});
		}


		// --- Окно состава ---
		$.brigadeMembers = (btn, id) => {
			let changed = false;
			ddrPopup({
				title: 'Состав бригады <span class="fz13px color-gray">'+_.escape($(btn).closest('tr').find('[name="name"]').val())+'</span>',
				url: 'ajax/brigades/members',
				params: {brigade: id, views},
				width: 1000,
				buttons: ['ui.close'],
			}).then(({wait, popper, loadData, onClose, dialog}) => {
				const reload = () => {
					changed = true;
					loadData('ajax/brigades/members', {brigade: id, views}, () => wait(false));
				};
				const run = (route, data, okText) => {
					wait();
					call(route, data)
						.then(() => { $.notify(okText); reload(); })
						.catch((e) => { wait(false); $.notify(errorText(e), 'error'); });
				};
				if (typeof onClose == 'function') onClose(() => { if (changed) reloadList(); });

				let transferMember = null;

				$.brigadeMemberAdd = (b, brigadeId) => {
					const box = $(popper).find('[brigadeadd]');
					const staffId = box.find('[name="staff_id"]').val();
					if (!staffId) return $.notify('Выберите сотрудника', 'error');
					run('add_member', {brigade: brigadeId, staff_id: staffId, joined_on: box.find('[name="joined_on"]').val()}, 'Сотрудник добавлен в состав');
				}

				$.brigadeMemberSave = (b, memberId) => {
					const row = $(b).closest('tr');
					run('update_member', {member: memberId, joined_on: row.find('[name="joined_on"]').val(), left_on: row.find('[name="left_on"]').val() || null}, 'Даты сохранены');
				}

				$.brigadeForeman = (b, brigadeId, staffId) => {
					run('set_foreman', {brigade: brigadeId, staff_id: Number(staffId) || null}, Number(staffId) ? 'Бригадир назначен' : 'Бригадир снят');
				}

				$.brigadeMemberRemove = (b, memberId) => {
					dialog('Удалить ошибочную запись состава? Если сотрудник действительно уходил из бригады, укажите дату «Вышел с» — тогда табель за прошлые дни сохранится.', {
						buttons: {
							'Отмена|light': ({closeDialog}) => closeDialog(),
							'Удалить|red': ({closeDialog}) => {
								closeDialog();
								run('remove_member', {member: memberId}, 'Запись удалена');
							},
						},
					});
				}

				$.brigadeMemberTransfer = (b, memberId) => {
					transferMember = memberId;
					const box = $(popper).find('[brigadetransfer]');
					box.find('[brigadetransfername]').text($(b).closest('tr').find('td:first span:first').text());
					box.removeAttr('hidden');
				}

				$.brigadeMemberTransferCancel = () => {
					transferMember = null;
					$(popper).find('[brigadetransfer]').attr('hidden', true);
				}

				$.brigadeMemberTransferDo = () => {
					const box = $(popper).find('[brigadetransfer]');
					const to = box.find('[name="to"]').val();
					if (!transferMember || !to) return $.notify('Выберите бригаду', 'error');
					run('transfer_member', {member: transferMember, to, date: box.find('[name="date"]').val()}, 'Сотрудник переведён');
				}
			});
		}
	});

</script>
