{{-- Вкладка прав приложения ЭМПИН-pro в окне прав сотрудника: галочки прав, у прав с настройками — настройки и их сводка --}}
<div apprights="{{$user}}">
	<div class="mb15px">
		<x-button variant="light" group="small" action="appRightsHistory:{{$user}}" px="10">История прав</x-button>
	</div>
	<div class="mb15px" apprightshistory hidden></div>

	@foreach($appRights as $item)
		<div class="mb10px" appright="{{$item['id']}}">
			<div class="d-flex align-items-center">
				<x-checkbox
					label="{{$item['title']}}"
					:checked="$item['granted']"
					action="{{$item['has_settings'] ? 'appRightCheck' : 'setPermissionToUser'}}:{{$user}},{{$item['id']}}"
					tag="permid:{{$item['id']}}"
					/>
				@if($item['has_settings'])
					<x-button variant="light" group="small" action="appRightEdit:{{$user}},{{$item['id']}}" title="Настройки" class="px-0 w3rem ml10px"><i class="fa-solid fa-gear"></i></x-button>
				@endif
			</div>
			@if($item['has_settings'])
				<div class="color-gray fz12px mt3px" apprightsummary>@foreach($item['summary'] as $line)<div>{{$line}}</div>@endforeach</div>
				<div class="mt5px" apprighteditor hidden></div>
			@endif
		</div>
	@endforeach
</div>

<script type="module">
	const errorText = (e) => {
		const d = e?.response?.data || e?.error || e || {};
		if (typeof d.error === 'string') return d.error;
		if (d.errors) return Object.values(d.errors)[0]?.[0];
		return d.message || 'Не удалось выполнить действие';
	};
	const call = (route, data, type = 'json') => axiosQuery('post', 'ajax/app_rights/'+route, data, type).then(({data, error}) => {
		if (error) throw error;
		return data;
	});

	// Состояние галочки так же, как его ведёт админка: свойство, атрибут checked и класс обёртки
	const setChecked = (input, state) => {
		const group = $(input).attr('inpgroup') ? $(input).attr('inpgroup')+'-' : '';
		$(input).prop('checked', state);
		if (state) input.setAttribute('checked', ''); else input.removeAttribute('checked');
		$(input).closest('.checkbox').toggleClass(group+'checkbox_checked', state);
	};

	const box = (permissionId) => $('[apprights] [appright="'+permissionId+'"]');

	const openEditor = (userId, permissionId) => {
		const editor = box(permissionId).find('[apprighteditor]');
		return call('editor', {user: userId, permission: permissionId}, 'text').then((html) => {
			editor.html(html).prop('hidden', false);
		}).catch((e) => $.notify(errorText(e), 'error'));
	};

	// Право с настройками без настроек не выдаётся: сначала настройки, выдача — после сохранения
	$.appRightCheck = (checkbox, userId, permissionId) => {
		if ($(checkbox).is(':checked')) {
			// Обработчик админки отмечает галочку после этого — сбрасываем следующим тактом
			setTimeout(() => setChecked(checkbox, false), 0);
			openEditor(userId, permissionId);
		} else {
			$.setPermissionToUser(checkbox, userId, permissionId);
		}
	};

	$.appRightEdit = (btn, userId, permissionId) => {
		const editor = box(permissionId).find('[apprighteditor]');
		if (!editor.prop('hidden')) {
			editor.prop('hidden', true).empty();
			return;
		}
		openEditor(userId, permissionId);
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

	$.appRightSave = (btn, userId, permissionId) => {
		const editor = $(btn).closest('[apprighteditor]');
		const rows = editor.find('[settingrows] [settingrow]').map((_, row) => {
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

		call('save', {user: userId, permission: permissionId, rows}).then((res) => {
			box(permissionId).find('[apprightsummary]').html((res.summary || []).map((line) => '<div>'+_.escape(line)+'</div>').join(''));
			editor.prop('hidden', true).empty();
			const checkbox = box(permissionId).find('input[type="checkbox"]').first()[0];
			if (!$(checkbox).is(':checked')) {
				// Выдача — тем же действием ERP, что и у остальных прав (с переводом прав роли в личные и историей)
				setChecked(checkbox, true);
				$.setPermissionToUser(checkbox, userId, permissionId);
			} else {
				$.notify('Запись успешно обновлена!');
			}
		}).catch((e) => $.notify(errorText(e), 'error'));
	};

	$.appRightsHistory = (btn, userId) => {
		const block = $(btn).closest('[apprights]').find('[apprightshistory]');
		if (!block.prop('hidden')) {
			block.prop('hidden', true).empty();
			return;
		}
		call('history', {user: userId}, 'text').then((html) => block.html(html).prop('hidden', false)).catch((e) => $.notify(errorText(e), 'error'));
	};
</script>
