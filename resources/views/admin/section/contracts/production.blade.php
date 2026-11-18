{{-- Окна «Производство» (кнопка в строке договора) и «План объекта» (из окна «Производство») — «Доработки ERP», пп. 1 и 5. Подключается в contracts.blade.php --}}
<script type="module">

	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};

	const call = (route, data = {}) => axiosQuery('post', 'ajax/contract_production/'+route, data, 'json').then(({data, error}) => {
		if (error) throw error;
		return data;
	});


	$.contractProduction = (btn, id, number) => {
		ddrPopup({
			title: 'Производство <span class="fz13px color-gray">объект '+_.escape(number)+'</span>',
			url: 'ajax/contract_production/info',
			params: {contract: id},
			width: 1000,
			buttons: ['ui.cancel', {title: 'План объекта', variant: 'neutral', action: 'contractProductionPlan'}, {title: 'Сохранить', variant: 'blue', action: 'contractProductionSave'}],
		}).then(({popper, wait, close}) => {
			// План объекта — своё окно; несохранённые изменения этого окна при переходе не сохраняются
			$.contractProductionPlan = () => {
				close();
				$.contractPlan(null, id, number);
			}

			$.contractProductionSave = () => {
				const box = $(popper).find('[productioninfo]');
				const brigadeIds = box.find('[brigadeid]')
					.filter((_, el) => $(el).find('input[type="checkbox"]').is(':checked'))
					.map((_, el) => Number($(el).attr('brigadeid'))).get();
				wait();
				call('save_info', {contract: id, responsible_staff_id: box.find('[name="responsible_staff_id"]').val() || null, brigade_ids: brigadeIds})
					.then(() => { $.notify('Сохранено: в приложении действует сразу'); close(); })
					.catch((e) => { wait(false); $.notify(errorText(e), 'error'); });
			}
		});
	}


	$.contractPlan = (btn, id, number) => {
		ddrPopup({
			title: 'План объекта <span class="fz13px color-gray">'+_.escape(number)+'</span>',
			url: 'ajax/contract_production/plan',
			params: {contract: id},
			width: 1000,
			buttons: ['ui.close', {title: 'Сохранить план', variant: 'blue', action: 'contractPlanSave'}],
		}).then(({popper, wait, loadData}) => {
			const box = () => $(popper).find('[contractplan]');

			// Единица измерения подставляется при выборе вида работ
			$(popper).on('change', '[planrows] select[name="work_type_id"]', function() {
				const units = box().data('units') || {};
				$(this).closest('tr').find('[unit]').text(units[$(this).val()] || '');
			});

			$.contractPlanRowAdd = () => {
				box().find('[planrows]').append(box().find('[planrowtemplate] tr').first().clone());
			}

			$.contractPlanRowRemove = (b) => $(b).closest('tr').remove();

			$.contractPlanRevisionToggle = (link, revisionId) => {
				const items = box().find('[revisionitems="'+revisionId+'"]');
				const open = items.is('[hidden]');
				items.prop('hidden', !open);
				$(link).text(open ? 'скрыть' : 'показать');
			}

			$.contractPlanSave = () => {
				const items = box().find('[planrows] tr').map((_, tr) => ({
					work_type_id: $(tr).find('[name="work_type_id"]').val() || null,
					quantity: $(tr).find('[name="quantity"]').val(),
				})).get();
				wait();
				call('save_plan', {contract: id, items})
					.then((res) => {
						$.notify(res?.changed ? 'План сохранён: новая редакция действует в приложении сразу' : 'Изменений нет — новая редакция не создана');
						loadData('ajax/contract_production/plan', {contract: id}, () => wait(false));
					})
					.catch((e) => { wait(false); $.notify(errorText(e), 'error'); });
			}
		});
	}

</script>
