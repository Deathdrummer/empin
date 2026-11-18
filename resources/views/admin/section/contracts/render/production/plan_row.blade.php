<tr class="h5rem" planrow>
	<td>
		@if(!empty($item['deleted']))
			<span class="color-gray">{{$item['name']}} — удалён из справочника</span>
			<input type="hidden" name="work_type_id" value="{{$item['work_type_id']}}">
		@else
			<x-select
				name="work_type_id"
				class="w100"
				:options="$workTypes"
				:value="$item['work_type_id'] ?? null"
				choose="Выберите вид работ"
				empty="Справочник видов работ пуст"
				choose-empty
				empty-has-value
				/>
		@endif
	</td>
	<td>
		<x-input name="quantity" value="{{isset($item['quantity']) ? rtrim(rtrim(number_format($item['quantity'], 3, '.', ''), '0'), '.') : ''}}" class="w100" />
	</td>
	<td><span class="color-gray" unit>{{$item['unit'] ?? ''}}</span></td>
	<td class="center">
		<x-button variant="red" group="small" action="contractPlanRowRemove" title="Убрать позицию" class="px-0 w3rem"><i class="fa-solid fa-trash-can"></i></x-button>
	</td>
</tr>
