{{-- Вкладка прав приложения ЭМПИН-pro в окне прав сотрудника: галочки прав, у прав с настройками — настройки и их сводка --}}
@php
	[$appSets, $appSetSuggested] = app(\App\Production\Erp\RightSets::class)->choices((int) $user);
@endphp
<x-input-group group="normal">
<div apprights="{{$user}}">
		<div class="d-flex align-items-center mb15px">
			<x-select name="app_set" class="w30rem mr10px" :options="$appSets" :value="$appSetSuggested" choose="Типовой набор" empty="Наборов нет" choose-empty empty-has-value />
			<x-button variant="neutral" group="small" action="appRightsSetAsk" px="10" class="mr10px">Применить набор</x-button>
			<x-button variant="light" group="small" action="appRightsHistory:{{$user}}" px="10">История прав</x-button>
		</div>
		<div class="d-flex align-items-center mb15px" apprightsapply hidden>
			<span class="mr10px">Заменить права приложения правами набора?</span>
			<x-button variant="blue" group="small" action="appRightsSetApply:{{$user}}" px="10" class="mr5px">Применить</x-button>
			<x-button variant="light" group="small" action="appRightsSetCancel" px="10">Отмена</x-button>
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
</x-input-group>

@include('admin.section.personal.render.app_rights.editor_js')

<script type="module">
	const call = (route, data, type = 'json') => axiosQuery('post', 'ajax/app_rights/'+route, data, type).then(({data, error}) => {
		if (error) throw error;
		return data;
	});
	const box = (permissionId) => $('[apprights] [appright="'+permissionId+'"]');

	const openEditor = (userId, permissionId) => {
		const editor = box(permissionId).find('[apprighteditor]');
		return call('editor', {user: userId, permission: permissionId}, 'text').then((html) => {
			editor.html(html).prop('hidden', false);
		}).catch((e) => $.notify($.appRightErrorText(e), 'error'));
	};

	// Право с настройками без настроек не выдаётся: сначала настройки, выдача — после сохранения
	$.appRightCheck = (checkbox, userId, permissionId) => {
		if ($(checkbox).is(':checked')) {
			// Обработчик админки отмечает галочку после этого — сбрасываем следующим тактом
			setTimeout(() => $.appRightSetChecked(checkbox, false), 0);
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

	$.appRightSave = (btn, userId, permissionId) => {
		const editor = $(btn).closest('[apprighteditor]');
		call('save', {user: userId, permission: permissionId, rows: $.appRightRows(editor)}).then((res) => {
			box(permissionId).find('[apprightsummary]').html((res.summary || []).map((line) => '<div>'+_.escape(line)+'</div>').join(''));
			editor.prop('hidden', true).empty();
			const checkbox = box(permissionId).find('input[type="checkbox"]').first()[0];
			if (!$(checkbox).is(':checked')) {
				// Выдача — тем же действием ERP, что и у остальных прав (с переводом прав роли в личные и историей)
				$.appRightSetChecked(checkbox, true);
				$.setPermissionToUser(checkbox, userId, permissionId);
			} else {
				$.notify('Запись успешно обновлена!');
			}
		}).catch((e) => $.notify($.appRightErrorText(e), 'error'));
	};

	$.appRightsHistory = (btn, userId) => {
		const block = $(btn).closest('[apprights]').find('[apprightshistory]');
		if (!block.prop('hidden')) {
			block.prop('hidden', true).empty();
			return;
		}
		call('history', {user: userId}, 'text').then((html) => block.html(html).prop('hidden', false)).catch((e) => $.notify($.appRightErrorText(e), 'error'));
	};

	$.appRightsSetAsk = (btn) => {
		const root = $(btn).closest('[apprights]');
		if (!root.find('[name="app_set"]').val()) return $.notify('Выберите набор', 'error');
		root.find('[apprightsapply]').prop('hidden', false);
	};

	$.appRightsSetCancel = (btn) => $(btn).closest('[apprightsapply]').prop('hidden', true);

	// Применение набора: вкладка перерисовывается, у прав без настроек в наборе открываются настройки
	$.appRightsSetApply = (btn, userId) => {
		const root = $(btn).closest('[apprights]');
		call('apply', {user: userId, set: root.find('[name="app_set"]').val()}).then((res) => {
			return call('tab', {user: userId}, 'text').then((html) => {
				root.replaceWith(html);
				$.notify('Набор применён');
				(res.need_settings || []).forEach((permissionId) => openEditor(userId, permissionId));
			});
		}).catch((e) => $.notify($.appRightErrorText(e), 'error'));
	};
</script>
