@aware([
	'settings'  => null,
])


@props([
	'id'        	=> 'simplist'.rand(0,9999999),
	'group'     	=> 'normal',
	'setting'   	=> false,
	'onRemove'		=> false,
	'onCreate'		=> false,
	'storage'		=> '/',
	'maxfilesize'	=> null,
	'filetypes'		=> null,
])


{{-- 
Пример
	fieldset="Выпадающий список|select|name_type"
	options="name_type;1:foo,2:bar"
--}}


<x-input-group group="{{$group}}">
	<div class="table">
		<table class="w100">
			<thead>
				<tr>
					@if($titles)
						@foreach($titles as $title)
							<td class="{{isset($title[1]) ? $title[1] : ''}}"><strong class="fz13px lh90 d-block">{{$title[0]}}</strong></td>
						@endforeach
					@endif
					<td class="p-0"></td>
					<td class="w5rem"></td>
				</tr>
			</thead>
			<tbody id="{{$id}}">
				@forelse($settingData($settings, $setting) as $row => $value) {{-- 'title' => 'Значение 1', 'text' => 'dfndfnsndn', --}}
					<tr class="h5rem" index="{{$row}}">
						@if($fields)
							@foreach($fields as $field)
								{{-- @if(!isset($value[$field['name']]))
									<td><p class="color-gray text-center">-</p></td>
									@continue
								@endif --}}
								<td
									@class([
										'top',
										'pt2rem' => $field['readonly'],
										'pt12px pb12px' => $field['type'] == 'file',
										/*'pt10px pb10px' => in_array($field['type'], ['checkbox', 'radio']),*/
										'pt10px pb10px' => in_array($field['type'], ['checkbox', 'radio', 'select', 'text', 'textarea', 'password', 'number' , 'search', 'date', 'tel', 'url', 'color']),
										
										/*'pt15px' => !$field['readonly'],*/
									])>
									@if($field['type'] == 'select' && isset($options[$field['name']]))
										@if($field['readonly'])
											<p
												class=""
												field="{{$field['name']}}"
												value="{{$value[$field['name']] ?? null}}"
												>{!!$value[$field['name']] ?? '<p class="color-gray text-center">-</p>'!!}</p>
										@else
											<x-dynamic-component
												:component="$field['type']"
												setting="{{$setting}}.{{$row}}.{{$field['name']}}"
												:options="isset($options[$field['name']]) ? $options[$field['name']] : []"
												empty="Ничего нет"
												choose="Не выбран"
												choose-empty
												empty-has-value
												value="{{$value[$field['name']] ?? null}}"
												class="w100"
												tag="field:{{$field['name']}}"
												/>
										@endif
											
									
									@elseif($field['type'] == 'radio' && isset($options[$field['name']]))
										
										@forelse($options[$field['name']] as $value => $label)
											<x-dynamic-component
												:component="$field['type']"
												setting="{{$setting}}.{{$row}}.{{$field['name']}}"
												label="{{$label}}"
												value="{{$value}}"
												class="w100"
												tag="field:{{$field['name']}}"
												/>
										@empty
										@endforelse
										
									@elseif(in_array($field['type'], ['text', 'password', 'number' , 'search', 'date', 'tel', 'url', 'color']))			
										@if($field['readonly'])
											<p
												class=""
												field="{{$field['name']}}"
												value="{{$value[$field['name']] ?? null}}"
												>{!!$value[$field['name']] ?? '<p class="color-gray text-center">-</p>'!!}</p>
										@else
											<x-dynamic-component
												component="input"
												:type="$field['type']"
												setting="{{$setting}}.{{$row}}.{{$field['name']}}"
												value="{{$value[$field['name']] ?? null}}"
												showrows="{{$field['type'] == 'number'}}"
												class="w100"
												tag="field:{{$field['name']}}"
												/>
										@endif
									@elseif($field['type'] == 'file')
										@if($field['readonly'])
											<p emptytext="Нет файла">{{$value[$field['name']]['name'] ?? null}}</p>
											<input type="hidden" value="{{$value[$field['name']]['path'] ?? null}}">
										@else
											<div class="row align-items-center" fileblock>
												<div class="col">
													<p
														class="fz12px color-black"
														filetitle
														emptytext="Файл не выбран">{{$value[$field['name']]['name'] ?? null}}{{isset($value[$field['name']]['ext']) ? '.'.$value[$field['name']]['ext'] : null}}</p>
													<input
														type="hidden"
														field="{{$field['name']}}"
														filedata
														value="{{json_encode($value[$field['name']] ?? null)}}"
														onchange="$.setSetting(this, '{{$setting}}.{{$row}}.{{$field['name']}}')">
												</div>
												<div class="col-auto">
													<x-buttons-group group="small" gx="3">
														<x-button
															variant="green"
															visible="{{isset($value[$field['name']]['path']) ? 1 : 0}}"
															tag="downloadfile"
															title="Выгрузить файл"
															>
															<i class="fa-solid fa-download"></i>
														</x-button>
														<x-button
															variant="blue"
															visible="{{!isset($value[$field['name']]['path']) ? 1 : 0}}"
															tag="addfile"
															title="Выбрать файл"
															>
															<i class="fa-solid fa-plus"></i>
														</x-button>
														<x-button
															variant="red"
															visible="{{isset($value[$field['name']]['path']) ? 1 : 0}}"
															tag="removefile"
															title="Удалить файл"
															>
															<i class="fa-solid fa-trash-can"></i>
														</x-button>
													</x-buttons-group>
												</div>
											</div>
										@endif
									@else
										@if($field['readonly'])
											<p
												class=""
												field="{{$field['name']}}"
												value="{{$value[$field['name']] ?? null}}"
												>{!!$value[$field['name']] ?? '<p class="color-gray text-center">-</p>'!!}</p>
										@else
											<x-dynamic-component
												:component="$field['type']"
												setting="{{$setting}}.{{$row}}.{{$field['name']}}"
												value="{{$value[$field['name']] ?? null}}"
												class="w100"
												tag="field:{{$field['name']}}"
												/>
										@endif
									@endif
								</td>
							@endforeach
							<td class="p-0"></td>
							<td class="center top pt15px">
								<x-button
									variant="red"
									class="w3rem"
									action="{{$id}}RemoveRow:{{$setting}}.{{$row}}"
									title="Удалить"
									><i class="fa-solid fa-trash-can"></i></x-button>									
							</td>
						@else
							<td colspan="{{count($titles)+2}}"><p>Нет данных</p></td>
						@endif
					</tr>
				@empty
				@endforelse
			</tbody>
			<tfoot>
				<td colspan="{{count($titles)+2}}" class="right">
					<x-button
						variant="blue"
						action="{{$id}}AddRow:#{{$id}},{{$fieldsToButton}},{{$stringOptions}},{{$setting}},{{$group}}"
						>{{__('ui.add')}}</x-button>
				</td>
			</tfoot>
		</table>
	</div>
</x-input-group>





<script type="module">
	let id = '{{$id}}',
		listId = '#{{$id}}',
		addRowAction = '{{$id}}AddRow',
		removeRowAction = '{{$id}}RemoveRow',
		onRemoveFunc = '{{$onRemove}}',
		onCreateFunc = '{{$onCreate}}',
		storage = '{{$storage}}',
		maxFileSize = '{{$maxfilesize}}',
		fileTypes = '{{$filetypes}}';
	
	
	$(listId).ddrInputs('change', (input) => {
		$(input).closest('tr').find('button[new]').removeAttrib('new');
	});
	
	
	$[addRowAction] = (btn, listSelector, fields, options, setting, group) => {
		
		//let row = $(listSelector).children('tr').length ? (parseInt($(listSelector).children('tr:last').attr('index')) + 1) : 0;
		let maxId = 0;
		$(listSelector).children('tr').each((k, row) => {
			const currentId = Number($(row).find('[field="id"]').val() || $(row).find('[field="id"]').attr('value'));
			if (currentId > maxId) maxId = currentId;
		});
		
		let simplelistAddBtnWait = $(btn).ddrWait({
			iconHeight: '20px',
			bgColor: '#ffffff91'
		});
		
		axiosQuery('post', 'ajax/simplelist', {
			id,
			row: maxId,
			fields,
			options,
			setting,
			group
		}, 'text').then(({data, error, status, headers}) => {
			if (error) {
				console.log(error);
				$.notify(error?.message, 'error');
			}
			
			if (data) $(listSelector).append(data);
			
			const tr = $(listSelector).find('tr[new]');
			
			if ($(tr).find('input[field="id"]').length) {
				$(tr).find('input[field="id"]').val(Number($(tr).attr('index')) + 1).triggerHandler('input');
				$(tr).find('button[new]').removeAttrib('new');
			}
			
			$(listSelector).find('tr[new]').ddrInputs('change', (input) => {
				$(input).closest('tr').find('button[new]').removeAttrib('new');
			});
			
			$(listSelector).find('tr[new]').removeAttrib('new');
			
			simplelistAddBtnWait.destroy();
			
			if (onCreateFunc && $[onCreateFunc]) {
				$[onCreateFunc]({tr, btn, listSelector, fields, options, setting, group});
			}
		});
	}
	
	
	
	
	
	$[removeRowAction] = (btn, setting) => {
		let hasRows = !!$(btn).closest('tr').siblings('tr').length;
		
		if ($(btn).hasAttr('new')) {
			if (hasRows) $(btn).closest('tr').remove();
			else $(btn).closest('tbody').empty();
			removeFile(btn);
			//$.notify('Запись успешно удалена!');
		} else {
			ddrPopup({
				width: 400,
				html: '<p class="color-red fz16px">Вы действительно хотите удалить запись</p>',
				buttons: ['Отмена', {action: 'simplelistRemoveRowAction', title: 'Удалить', variant: 'red'}],
				buttonsAlign: 'center',
				buttonsGroup: 'small',
				winClass: 'ddrpopup_dialog',
				centerMode: true,
				topClose: false
			}).then(({state, wait, setTitle, setButtons, loadData, setHtml, setLHtml, dialog, close, onScroll, disableButtons, enableButtons, setWidth}) => { //isClosed
				$.simplelistRemoveRowAction = () => {
					wait();
					$(btn).closest('tr').find('input, textarea, select, button').ddrInputs('disable');
					
					axiosQuery('delete', 'api/settings', {
						path: setting,
					}, 'json').then(async ({data, error, status, headers}) => {
						if (error) {
							console.log(error);
							$.notify(error?.message, 'error');
							
							if (error.errors) {
								$.each(error.errors, function(field, errors) {
									$(btn).closest('tr').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
								});
							}
							
							wait(false);
							return;
						}
						
						if (data) {
							await removeFile(btn);
							
							if (onRemoveFunc && $[onRemoveFunc]) {
								$[onRemoveFunc]($(btn).closest('tr'), () => {
									if (hasRows) $(btn).closest('tr').remove();
									else $(btn).closest('tbody').empty();
									$.notify('Запись успешно удалена!');
								});
								
								//if (!callback || typeof callback !== 'function') return;
								//callback();
								
							} else {
								if (hasRows) $(btn).closest('tr').remove();
								else $(btn).closest('tbody').empty();
								$.notify('Запись успешно удалена!');
							}
							
							close();
						}
						
						//$(btn).closest('tr').find('input, textarea, select, button').ddrInputs('enable');
					});
				}
			});		
		}	
	}
	
	
	
	
	

	$(listId).on(tapEvent, '[addfile]', (e) => {
		const fileBlock = $(e.target).closest('[fileblock]'),
			td = $(e.target).closest('td'),
			fileDataInp = $(fileBlock).find('[filedata]'),
			fileTitle = $(fileBlock).find('[filetitle]'),
			addFileBtn = e.currentTarget,
			downloadFileBtn = $(fileBlock).find('[downloadfile]'),
			removeFileBtn = $(fileBlock).find('[removefile]');
		
		let waitObj;
			
		$.ddrChooseFiles({
			//multiple,
			init(...data) {
				waitObj = $(td).ddrWait({
					iconHeight: '26px',
					bgColor: '#ffffffc7'
				});
			},
			//preload,
			async callback({file, name, ext, size, type, isImage, key}) {
				let success = true,
					fileSize = (size / 1024 / 1024).toFixed(2);
				
				if (maxFileSize && fileSize > maxFileSize) {
					$.notify('Размер файла превышает максимально допустимый!', 'error');
					success = false;
				}
				
				if (fileTypes && !fileTypes.split('|').includes(ext)) {
					$.notify('Недопустимый формат файла!', 'error');
					success = false;
				}
				
				if (!success) return;
				
				const {path} = await uploadFile({
					file: file,
					storage: storage,
				});
				
				$(fileDataInp).val(JSON.stringify({name, ext, path})).triggerHandler('change');
				$(fileTitle).text(`${name}.${ext}`);
				
				$(removeFileBtn).closest('.col-auto.hidden').removeClass('hidden');
				$(addFileBtn).closest('.col-auto:not(.hidden)').addClass('hidden');
				$(downloadFileBtn).closest('.col-auto.hidden').removeClass('hidden');
			},
			done() {
				waitObj.destroy();
			},
			fail() {
				waitObj.destroy();
				console.log('fail');
			}
		});

	});
	
	
	
	
	
	
	
	
	$(listId).on(tapEvent, '[downloadfile]', async (e) => {
		const fileBlock = $(e.target).closest('[fileblock]'),
			td = $(e.target).closest('td'),
			fileDataInp = $(fileBlock).find('[filedata]'),
			fileTitle = $(fileBlock).find('[filetitle]'),
			addFileBtn = $(fileBlock).find('[addfile]'),
			downloadFileBtn = e.currentTarget,
			removeFileBtn = $(fileBlock).find('[removefile]');
		
		
		
		const fileData = JSON.parse($(fileDataInp).val());
		
		
		$(downloadFileBtn).ddrInputs('disable');
		const {data, error, status, headers} = await axiosQuery('post', 'site/contracts/export_act_template', {path: fileData['path'], 'name': fileData['name']}, 'blob');
								
		if (error) {
			$.notify('Не удалось скачать файл!', 'error');
			console.log(error?.message, error?.errors);
			return;
		}
		
		exportFile({data, headers, filename: fileData['name']}, () => {
			$(downloadFileBtn).ddrInputs('enable');
		});
		
	});
	
	
	
	
	
	
	
	$(listId).on(tapEvent, '[removefile]', async (e) => {
		
		const fileBlock = $(e.target).closest('[fileblock]'),
			td = $(e.target).closest('td'),
			fileDataInp = $(fileBlock).find('[filedata]'),
			{path} = JSON.parse(fileDataInp.val()),
			fileTitle = $(fileBlock).find('[filetitle]'),
			addFileBtn = $(fileBlock).find('[addfile]'),
			downloadFileBtn = $(fileBlock).find('[downloadfile]'),
			removeFileBtn = e.currentTarget,
			formData = new FormData();
		
		const {destroy} = $(td).ddrWait({
			iconHeight: '26px',
			bgColor: '#ffffffc7'
		});
		
		formData.append('path', path);
		formData.append('_method', 'delete');
		const {data} = await axios.post('/ajax/upload_file', formData);
		
		
		$(fileDataInp).val('').triggerHandler('change');
		$(fileTitle).empty();
		
		$(removeFileBtn).closest('.col-auto:not(.hidden)').addClass('hidden');
		$(addFileBtn).closest('.col-auto.hidden').removeClass('hidden');
		$(downloadFileBtn).closest('.col-auto:not(.hidden)').addClass('hidden');
		
		destroy();
		
		//console.log($(addFileBtn).closest('.col-auto:not(.hidden)').length, data?.is_deleted);
		return data;
	});
	
	
	
	
	
	
	async function uploadFile({file = null, storage = null}) {
		const formData = new FormData();
		
		formData.append('file', file);
		formData.append('storage', storage);
		
		try {
			const {data} = await axios.post('/ajax/upload_file', formData, {headers: {'Content-Type': 'multipart/form-data'}});
			return data;
		} catch(err) {
			console.log(err);
			$.notify('Ошибка загрузки файла!', 'error');
			return false;
		}
	}
	
	
	
	async function removeFile(btn = null) {
		const tr = $(btn).closest('tr'),
			fileBlock = $(tr).find('[fileblock]'),
			fileDataInp = $(fileBlock).find('[filedata]'),
			fileData = fileDataInp?.val(),
			{path} = isJson(fileData) ? JSON.parse(fileData) : {};
		
		if (!path) return;
		
		const formData = new FormData();
		
		formData.append('path', path);
		formData.append('_method', 'delete');
		const {data} = await axios.post('/ajax/upload_file', formData);
	}
	
	
	
	
	
	//if ($(listId).find('tr').length == 0) $(listId).empty();
	
	
	
</script>