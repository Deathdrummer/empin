@if($contractsSmeta)
	<div class="row">
		@foreach($contractsSmeta as $item)
			<div class="col-auto">
		  		<div
		  			style="background-color: {{$item['color']}};"
		  			@class([
		  				'lightsitem',
		  				'lightsitem_active' => $item['id'] == $color,
		  			])
		  			onclick="$.contractSetData(this, {{$contractId}}, {{$departmentId}}, {{$stepId}}, 5)"
		  			colorid="{{$item['id']}}"
		  			color="{{$item['color']}}"
		  			title="{{$item['name']}}"
		  			></div>
			</div>
		@endforeach
		<div class="col-auto">
	  		<div
	  			@class([
	  				'lightsitem',
	  				'lightsitem_active' => !$color,
	  				'border-all border-gray-300 d-flex align-items-center justify-content-center'
	  			])
	  			onclick="$.contractSetData(this, {{$contractId}}, {{$departmentId}}, {{$stepId}}, 5)"
	  			:colorid="null"
	  			title="{{!$color ? 'Нет статуса' : 'Убрать статус'}}"
	  			><small class="fz10px">нет</small></div>
		</div>
	</div>
@else
	<p class="color-gray-400 text-center pt-6px">Нет цветов</p>
@endif