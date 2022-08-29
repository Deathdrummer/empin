<x-input-group group="{{$group}}">
	<tr
		class="h5rem"
		index="{{$row}}"
		new
		>
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