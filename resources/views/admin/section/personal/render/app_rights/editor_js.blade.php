{{-- Общее для правки настроек права: окно прав сотрудника и окно типового набора --}}
<script type="module">
	// Состояние галочки так же, как его ведёт админка: свойство, атрибут checked и класс обёртки
	$.appRightSetChecked = (input, state) => {
		const group = $(input).attr('inpgroup') ? $(input).attr('inpgroup')+'-' : '';
		$(input).prop('checked', state);
		if (state) input.setAttribute('checked', ''); else input.removeAttribute('checked');
		$(input).closest('.checkbox').toggleClass(group+'checkbox_checked', state);
	};

	$.appRightErrorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};

	$.appRightCancel = (btn) => $(btn).closest('[apprighteditor]').prop('hidden', true).empty();

	// Новая строка настройки: свои id у полей и своя группа переключателей
	$.appRightRowAdd = (btn) => {
		const editor = $(btn).closest('[apprighteditor]');
		const row = editor.find('[settingtemplate] [settingrow]').first().clone();
		const uid = 'n'+Date.now();
		row.find('[id]').each(function() {
			const id = this.id;
			this.id = id+uid;
			row.find('label[for="'+id+'"]').attr('for', this.id);
		});
		row.find('input[type="radio"]').attr('name', 'scope'+uid).attr('fieldset', 'scope'+uid);
		editor.find('[settingrows]').append(row);
	};

	$.appRightRowRemove = (btn) => $(btn).closest('[settingrow]').remove();

	$.appRightFilter = (input) => {
		const q = $(input).val().trim().toLowerCase();
		$(input).closest('[checklist]').find('[checkitem]').each(function() {
			$(this).toggle(!q || $(this).text().toLowerCase().includes(q));
		});
	};

	$.appRightRows = (editor) => editor.find('[settingrows] [settingrow]').map((_, row) => {
		const r = $(row);
		const checked = (list) => r.find('[checklist="'+list+'"] [checkitem]').filter((_, el) => $(el).find('input[type="checkbox"]').is(':checked')).map((_, el) => Number($(el).attr('checkitem'))).get();
		return {
			depth_days: r.find('[name="depth_days"]').val() || null,
			depth_unlimited: r.find('[name="depth_unlimited"]').is(':checked') ? 1 : 0,
			scope: r.find('input[type="radio"]:checked').val() || null,
			staff_ids: checked('staff'),
			brigade_ids: checked('brigades'),
			date_from: r.find('[name="date_from"]').val() || null,
			date_to: r.find('[name="date_to"]').val() || null,
		};
	}).get();
</script>
