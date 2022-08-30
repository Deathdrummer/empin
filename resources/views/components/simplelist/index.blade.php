@aware([
	'settings'  => null,
])


@props([
	'id'        => 'simplist'.rand(0,9999999),
	'group'     => 'normal',
	'setting'   => false,
	'onRemove'	=> false,
	'oncreate'	=> false,
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
							<td class="{{isset($title[1]) ? $title[1] : ''}}"><strong>{{$title[0]}}</strong></td>
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
										'pt15px' => !$field['readonly'],
									])
									>
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
		onRemoveFunc = '{{$onRemove}}';
	
	
	$(listId).ddrInputs('change', (input) => {
		$(input).closest('tr').find('button[new]').removeAttrib('new');
	});
	
	
	$[addRowAction] = (btn, listSelector, fields, options, setting, group) => {
		
		let row = $(listSelector).children('tr').length ? (parseInt($(listSelector).children('tr:last').attr('index')) + 1) : 0;
		
		let simplelistAddBtnWait = $(btn).ddrWait({
			iconHeight: '20px',
			bgColor: '#ffffff91'
		});
		
		axiosQuery('post', 'ajax/simplelist', {
			id,
			row,
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
			
			$(listSelector).find('tr[new]').ddrInputs('change', (input) => {
				$(input).closest('tr').find('button[new]').removeAttrib('new');
			});
			
			$(listSelector).find('tr[new]').removeAttrib('new');
			
			simplelistAddBtnWait.destroy();
		});
	}
	
	
	
	
	
	$[removeRowAction] = (btn, setting) => {
		let hasRows = !!$(btn).closest('tr').siblings('tr').length;
		
		if ($(btn).hasAttr('new')) {
			if (hasRows) $(btn).closest('tr').remove();
			else $(btn).closest('tbody').empty();
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
					close();
					$(btn).closest('tr').find('input, textarea, select, button').ddrInputs('disable');
					
					axiosQuery('delete', 'api/settings', {
						path: setting,
					}, 'json').then(({data, error, status, headers}) => {
						if (error) {
							console.log(error);
							$.notify(error?.message, 'error');
							
							if (error.errors) {
								$.each(error.errors, function(field, errors) {
									$(btn).closest('tr').find('[name="'+field+'"]').ddrInputs('error', errors[0]);
								});
							}
						}
						
						if (data) {
							if (onRemoveFunc) {
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
						}
						
						//$(btn).closest('tr').find('input, textarea, select, button').ddrInputs('enable');
					});
				}
			});		
		}	
	}
	
	
	
	
	//if ($(listId).find('tr').length == 0) $(listId).empty();
	
	
	
</script>