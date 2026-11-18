{{-- Окно типового набора: права приложения галочками, у прав с настройками — настройки набора --}}
<div appsetrights="{{$set->id}}">
	<x-input-group group="normal">
		@foreach($rights as $item)
			<div class="mb10px" appright="{{$item['id']}}">
				<div class="d-flex align-items-center">
					<x-checkbox label="{{$item['title']}}" :checked="$item['granted']" action="appSetRightToggle:{{$set->id}},{{$item['id']}}" />
					@if($item['has_settings'])
						<x-button variant="light" group="small" action="appSetSettingsEdit:{{$set->id}},{{$item['id']}}" title="Настройки" class="px-0 w3rem ml10px"><i class="fa-solid fa-gear"></i></x-button>
					@endif
				</div>
				@if($item['has_settings'])
					<div class="color-gray fz12px mt3px" apprightsummary>@foreach($item['summary'] as $line)<div>{{$line}}</div>@endforeach</div>
					<div class="mt5px" apprighteditor hidden></div>
				@endif
			</div>
		@endforeach
	</x-input-group>
</div>

@include('admin.section.personal.render.app_rights.editor_js')

<script type="module">
	const call = (route, data, type = 'json') => axiosQuery('post', 'ajax/app_sets/'+route, data, type).then(({data, error}) => {
		if (error) throw error;
		return data;
	});
	const box = (permissionId) => $('[appsetrights] [appright="'+permissionId+'"]');

	$.appSetRightToggle = (checkbox, setId, permissionId) => {
		const granted = $(checkbox).is(':checked') ? 1 : 0;
		call('set_right', {set: setId, permission: permissionId, granted}).then(() => {
			if (!granted) box(permissionId).find('[apprightsummary]').empty();
		}).catch((e) => {
			setTimeout(() => $.appRightSetChecked(checkbox, !granted), 0);
			$.notify($.appRightErrorText(e), 'error');
		});
	};

	$.appSetSettingsEdit = (btn, setId, permissionId) => {
		const editor = box(permissionId).find('[apprighteditor]');
		if (!editor.prop('hidden')) {
			editor.prop('hidden', true).empty();
			return;
		}
		call('editor', {set: setId, permission: permissionId}, 'text').then((html) => editor.html(html).prop('hidden', false))
			.catch((e) => $.notify($.appRightErrorText(e), 'error'));
	};

	$.appSetSettingsSave = (btn, setId, permissionId) => {
		const editor = $(btn).closest('[apprighteditor]');
		call('save_settings', {set: setId, permission: permissionId, rows: $.appRightRows(editor)}).then((res) => {
			box(permissionId).find('[apprightsummary]').html((res.summary || []).map((line) => '<div>'+_.escape(line)+'</div>').join(''));
			editor.prop('hidden', true).empty();
			$.notify('Запись успешно обновлена!');
		}).catch((e) => $.notify($.appRightErrorText(e), 'error'));
	};
</script>
