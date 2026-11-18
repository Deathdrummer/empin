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

		<p class="mt20px mb5px"><strong>Утверждённая готовность объекта</strong></p>
		@if($info['readiness_percent'] !== null)
			<p>
				<strong class="fz16px">{{rtrim(rtrim(number_format($info['readiness_percent'], 2, ',', ' '), '0'), ',')}} %</strong>
				@if($info['readiness_changed_at'])
					<span class="color-gray fz12px ml5px">{{$info['readiness_changed_at']->copy()->timezone(config('production.timezone'))->format('d.m.Y H:i')}}</span>
				@endif
			</p>
		@else
			<p class="color-gray">-</p>
		@endif
	</x-input-group>
</div>
