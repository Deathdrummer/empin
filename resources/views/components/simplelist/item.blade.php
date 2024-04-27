<x-input-group group="{{$group}}">
	<tr
		class="h5rem"
		index="{{$row}}"
		new
		>
		@if($fields)
			@foreach($fields as $field)
				<td
					@class([
						'top',
						'pt12px pb12px' => $field['type'] == 'file',
						/*'pt10px pb10px' => in_array($field['type'], ['checkbox', 'radio']),*/
						'pt10px pb10px' => in_array($field['type'], ['checkbox', 'radio', 'select', 'text', 'textarea', 'password', 'number' , 'search', 'date', 'tel', 'url', 'color']),
						
						/*'pt15px' => !$field['readonly'],*/
					])>
					@if($field['type'] == 'select' && isset($options[$field['name']]))
						<x-dynamic-component
							:component="$field['type']"
							setting="{{$setting}}.{{$row}}.{{$field['name']}}"
							:options="isset($options[$field['name']]) ? $options[$field['name']] : []"
							empty="Ничего нет"
							choose="Не выбран"
							choose-empty
							class="w100"
							tag="field:{{$field['name']}}"
							/>
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
						<x-dynamic-component
							component="input"
							:type="$field['type']"
							setting="{{$setting}}.{{$row}}.{{$field['name']}}"
							class="w100"
							showrows="{{$field['type'] == 'number'}}"
							tag="field:{{$field['name']}}"
							/>
					@elseif($field['type'] == 'file')
						<div class="row align-items-center" fileblock>
							<div class="col">
								<p class="fz12px color-black" filetitle emptytext="Файл не выбран"></p>
								
								<input
									type="hidden"
									field="{{$field['name']}}"
									filedata
									value=""
									onchange="$.setSetting(this, '{{$setting}}.{{$row}}.{{$field['name']}}')">
							</div>
							<div class="col-auto">
								<x-buttons-group group="small" gx="5">
									<x-button
										variant="blue"
										tag="addfile"
										visible="{{!isset($value[$field['name']]['path']) ? 1 : 0}}"
										>
										<i class="fa-solid fa-plus"></i>
									</x-button>
									<x-button
										variant="red"
										visible="{{isset($value[$field['name']]['path']) ? 1 : 0}}"
										tag="removefile"
										>
										<i class="fa-solid fa-trash-can"></i>
									</x-button>
								</x-buttons-group>
							</div>
						</div>
					@else
						<x-dynamic-component
							:component="$field['type']"
							setting="{{$setting}}.{{$row}}.{{$field['name']}}"
							class="w100"
							tag="field:{{$field['name']}}"
							/>
					@endif
				</td>
			@endforeach
			<td class="p-0"></td>
			<td class="center top pt15px">
				<x-button
					new
					variant="red"
					class="w3rem"
					action="{{$id}}RemoveRow:{{$setting}}.{{$row}}"
					><i class="fa-solid fa-trash-can"></i></x-button>
			</td>
		@else
			<td colspan="{{count($titles)+2}}"><p>Нет данных</p></td>
		@endif
	</tr>
</x-input-group>