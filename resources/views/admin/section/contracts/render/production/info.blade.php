<div productioninfo="{{$contract->id}}">
	<x-input-group group="normal">
		<p class="mb5px">
			<strong>Ответственный начальник участка</strong>
		</p>
		<x-select
			name="responsible_staff_id"
			class="w60rem"
			:options="$staff"
			:value="$info['responsible_staff_id']"
			choose="Без ответственного"
			empty="Нет сотрудников"
			choose-empty
			empty-has-value
			/>

		<p class="mt20px mb5px">
			<strong>Закреплённые бригады</strong>
		</p>
		@if($brigades->isEmpty())
			<p class="color-gray">Бригад нет</p>
		@else
			<div class="row gx-20">
				@foreach($brigades->chunk((int) ceil($brigades->count() / 3)) as $chunk)
					<div class="col">
						@foreach($chunk as $brigade)
							@php
								// Подпись галочки — HTML (компонент выводит её как есть): название, бригадир, пометка о расформировании
								$label = e($brigade['name'])
									.($brigade['foreman'] ? ' <span class="color-gray fz12px">'.e($brigade['foreman']).'</span>' : '')
									.($brigade['disbanded'] ? ' <span class="color-red fz12px">расформирована</span>' : '');
							@endphp
							<div class="mb10px" brigadeid="{{$brigade['id']}}">
								<x-checkbox :label="$label" :checked="$brigade['checked']" />
							</div>
						@endforeach
					</div>
				@endforeach
			</div>
		@endif

		@php
			$tz = config('production.timezone');
			$pct = fn ($p) => $p === null ? '-' : rtrim(rtrim(number_format($p, 2, ',', ' '), '0'), ',');
			$shown = $info['readiness_percent'] !== null ? rtrim(rtrim(number_format($info['readiness_percent'], 2, '.', ''), '0'), '.') : '';
		@endphp
		<p class="mt20px mb5px"><strong>Готовность объекта, %</strong></p>
		<div class="d-flex align-items-center" readinessshown="{{$shown}}">
			<x-input type="number" name="readiness" value="{{$shown}}" placeholder="" class="w16rem" />
		</div>

		<p class="mt20px mb5px"><strong>История готовности</strong></p>
		@forelse($readinessHistory as $event)
			<div class="mb5px">
				<span>{{$event['at'] ? $event['at']->copy()->timezone($tz)->format('d.m.Y H:i') : ''}}</span>
				<span class="color-gray fz12px ml5px">{{$event['who'] ?? '-'}}</span>
				<span class="ml5px">· {{$pct($event['before'])}} → {{$pct($event['after'])}} %</span>
			</div>
		@empty
			<p class="color-gray">Изменений нет</p>
		@endforelse
	</x-input-group>
</div>
