<x-input-group group="normal">
	<div settingrows>
		@foreach($rows as $row)
			@include('admin.section.personal.render.app_rights.row', ['row' => $row, 'uid' => 'r'.$loop->index.$permissionId])
		@endforeach
	</div>
	<div settingtemplate hidden>
		@include('admin.section.personal.render.app_rights.row', ['row' => null, 'uid' => 't'.$permissionId])
	</div>
	<div class="d-flex mt5px mb10px">
		<x-button variant="neutral" group="small" action="appRightRowAdd" px="10" class="mr5px">Добавить настройку</x-button>
		<x-button variant="blue" group="small" action="appRightSave:{{$user}},{{$permissionId}}" px="10" class="mr5px">Сохранить</x-button>
		<x-button variant="light" group="small" action="appRightCancel" px="10">Отмена</x-button>
	</div>
</x-input-group>
