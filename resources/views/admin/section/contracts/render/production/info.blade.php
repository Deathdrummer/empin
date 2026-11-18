<div productioninfo="{{$contract->id}}">
	<x-input-group group="normal">
		<p class="mb5px">
			<strong>Ответственный начальник участка</strong>
			<span class="color-gray fz12px">один на объект; объект может быть и без ответственного. Задания на объект может ставить и не ответственный — приложение его предупредит</span>
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
			<span class="color-gray fz12px">несколько; по фильтру «Бригада» в приложении объект виден, кто бы ни был ответственным</span>
		</p>
		@if($brigades->isEmpty())
			<p class="color-gray">Бригад пока нет — их заводят в «Производство → Бригады»</p>
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
					<span class="color-gray fz12px ml5px">утверждена в приложении {{$info['readiness_changed_at']->copy()->timezone(config('production.timezone'))->format('d.m.Y H:i')}}</span>
				@endif
			</p>
		@else
			<p class="color-gray">в приложении ещё не утверждалась</p>
		@endif
		<p class="color-gray fz12px mt5px">Готовность утверждает начальник участка в приложении; ERP хранит значение и здесь его не меняет.</p>
	</x-input-group>
</div>
