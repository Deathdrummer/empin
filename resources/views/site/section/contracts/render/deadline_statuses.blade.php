<h4 class="fz16px mb1rem">Задать цвет статуса</h4>
@if($deadlineStatuses)
	<div class="row row-cols-4">
		@foreach($deadlineStatuses as $key => $status)
			<div class="col">
				<div class="pointer d-block" onclick="$.setColorStatus({{$key}}, '{{$status['color']}}', '{{$status['name']}}')">
					<div
						@class([
							'h5rem',
							'w5rem',
							'iconed',
							'border-all',
							'border-gray-400',
							'border-rounded-circle',
						])
						style="background-color: {{$status['color']}};"
						></div>
					<p class="fz10px lh100 mt5px">{{$status['name']}}</p>
				</div>
			</div>
		@endforeach
		
		<div class="col">
			<div class="pointer" onclick="$.setColorStatus(null)">
				<div
					@class([
						'h5rem',
						'w5rem',
						'iconed',
						'border-all',
						'border-gray-400',
						'border-rounded-circle',
					])></div>
				<p class="fz10px mt5px">Убрать</p>
			</div>
		</div>
	</div>
@else
	<p class="color-light text-center fz14px">Нет статусов</p>
@endif