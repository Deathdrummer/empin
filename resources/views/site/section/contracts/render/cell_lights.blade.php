<div class="row">
	@foreach([1 => 'yellow', 2 => 'green', 3 => 'red'] as $colorCode => $colorName)
		<div class="col-auto">
	  		<div
	  			@class([
	  				'lightsitem',
	  				'lightsitem_active' => $colorCode == $color,
	  				'bg-'.$colorName
	  			])
	  			onclick="$.contractSetData(this, {{$contractId}}, {{$departmentId}}, {{$stepId}}, 5)"
	  			color="{{$colorCode}}"
	  			></div>
		</div>
	@endforeach
</div>