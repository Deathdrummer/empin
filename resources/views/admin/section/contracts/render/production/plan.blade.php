@php
	$tz = config('production.timezone');
	$qty = fn ($q) => rtrim(rtrim(number_format($q, 3, ',', ' '), '0'), ',');
@endphp
<div contractplan="{{$contract->id}}" data-units='@json(collect($workTypes)->mapWithKeys(fn ($t) => [$t['value'] => $t['unit']]))'>

	<x-input-group group="normal">
		<div class="table">
			<table class="w100">
				<thead>
					<tr>
						<td><strong>Вид работ</strong></td>
						<td class="w16rem"><strong>Количество</strong></td>
						<td class="w8rem"><strong>Ед.</strong></td>
						<td class="w6rem"></td>
					</tr>
				</thead>
				<tbody planrows>
					@foreach($current['items'] ?? [] as $item)
						@include('admin.section.contracts.render.production.plan_row', ['item' => $item, 'workTypes' => $workTypes])
					@endforeach
				</tbody>
				<tbody planrowtemplate hidden>
					@include('admin.section.contracts.render.production.plan_row', ['item' => [], 'workTypes' => $workTypes])
				</tbody>
			</table>
		</div>
	</x-input-group>
	<div class="mt10px">
		<x-button variant="neutral" group="small" action="contractPlanRowAdd" px="15">Добавить позицию</x-button>
	</div>

	<p class="mt20px mb5px"><strong>Редакции плана</strong></p>
	@forelse($revisions as $revision)
		<div class="mb5px">
			<span>{{$revision['at'] ? $revision['at']->copy()->timezone($tz)->format('d.m.Y H:i') : ''}}</span>
			<span class="color-gray fz12px ml5px">{{$revision['admin'] ?? '-'}}</span>
			<span class="ml5px">· {{count($revision['items']) ?: 'план убран'}}{{count($revision['items']) ? ' поз.' : ''}}</span>
			@if($loop->first)<span class="color-green fz12px ml5px">действующая</span>@endif
			@if($revision['items'])
				<span class="pointer color-blue fz12px ml10px" onclick="$.contractPlanRevisionToggle(this, {{$revision['id']}})">показать</span>
			@endif
			<div class="mt5px mb10px" revisionitems="{{$revision['id']}}" hidden>
				<table class="w100">
					@foreach($revision['items'] as $item)
						<tr class="h3rem">
							<td @class(['color-gray' => $item['deleted']])>{{$item['name']}}@if($item['deleted']) (удалён из справочника)@endif</td>
							<td class="w16rem">{{$qty($item['quantity'])}} {{$item['unit']}}</td>
						</tr>
					@endforeach
				</table>
			</div>
		</div>
	@empty
		<p class="color-gray">Плана нет</p>
	@endforelse
</div>
