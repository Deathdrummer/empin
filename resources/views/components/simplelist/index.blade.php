@aware([
	'settings'  => null,
])


@props([
	'id'        => 'simplist'.rand(0,9999999),
	'group'     => 'normal',
	'setting'   => false,
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
								<td class="top pt15px">
									@if($field['type'] == 'select' && isset($options[$field['name']]))
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
											/>
									
									@elseif($field['type'] == 'radio' && isset($options[$field['name']]))
										
										@forelse($options[$field['name']] as $value => $label)
											<x-dynamic-component
												:component="$field['type']"
												setting="{{$setting}}.{{$row}}.{{$field['name']}}"
												label="{{$label}}"
												value="{{$value}}"
												class="w100"
												/>
										@empty
										@endforelse
										
									@elseif(in_array($field['type'], ['text', 'password', 'number' , 'search', 'date', 'tel', 'url', 'color']))			
										<x-dynamic-component
											component="input"
											:type="$field['type']"
											setting="{{$setting}}.{{$row}}.{{$field['name']}}"
											value="{{$value[$field['name']] ?? null}}"
											showrows="{{$field['type'] == 'number'}}"
											class="w100"
											/>
									@else
										<x-dynamic-component
											:component="$field['type']"
											setting="{{$setting}}.{{$row}}.{{$field['name']}}"
											value="{{$value[$field['name']] ?? null}}"
											class="w100"
											/>
									@endif
								</td>
							@endforeach
							<td class="p-0"></td>
							<td class="center top pt15px">
								<x-button
									variant="red"
									class="w3rem"
									action="simplelistRemoveRow:{{$setting}}.{{$row}}"
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
						action="simplelistAddRow:#{{$id}},{{$fieldsToButton}},{{$stringOptions}},{{$setting}},{{$group}}"
						>Добавить</x-button>
				</td>
			</tfoot>
		</table>
	</div>
</x-input-group>





<script type="module">
	let listId = '#{{$id}}';
	
	//if ($(listId).find('tr').length == 0) $(listId).empty();
	
	$(listId).ddrInputs('change', (input) => {
		$(input).closest('tr').find('button[new]').removeAttrib('new');
	});
	
</script>