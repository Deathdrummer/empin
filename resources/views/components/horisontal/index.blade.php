@props([
	'id'	=> 'horisontal'.rand(0,9999999),
	'speed'	=> 100,
	'step'	=> 100,
	'ignore'	=> '[noscroll]'
])


<div class="horisontal" id="{{$id}}">
	<div @class([
			'horisontal__track',
		])>{{$slot}}</div>
</div>

<script type="module">
	let selector = '#{{$id}}',
		ignoreSelectors = '{{$ignore}}',
		step = '{{$step}}',
		speed = '{{$speed}}';
	
	//Горизонтальная прокрутка блока мышью и колесиком
	//	- шаг прокрутки (для колеса)
	//	- скорость прокрутки (для колеса)
	//	- разрешить прокрутку колесом
	$(selector).ddrScrollX(step, speed, true, ignoreSelectors);
</script>