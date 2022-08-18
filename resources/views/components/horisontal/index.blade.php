@props([
	'id'			=> 'horisontal'.rand(0,9999999),
	'space'			=> null,
	'mousewheel'	=> false,
	'step'			=> 100,
	'speed'			=> 100,
	'ignore'		=> '[noscroll]'
])


<div class="horisontal" id="{{$id}}">
	
	<div class="horisontal__track">{{$slot}}</div>
	
	
	{{-- <div style="width: calc(100% - 20px);">
		<div @class([
			'row',
			'flex-nowrap',
			'gx-'.$space => $space
		])>{{$slot}}</div>
	</div> --}}
</div>

<script type="module">
	let selector = '#{{$id}}',
		mousewheel = !!'{{$mousewheel}}',
		step = parseInt('{{$step}}'),
		speed = parseInt('{{$speed}}'),
		ignore = '{{$ignore}}';
	
	if (!selector) throw new Error('horisontal ошибка! не передан селектор!');
	
	
	//Горизонтальная прокрутка блока мышью и колесиком
	//	- шаг прокрутки (для колеса)
	//	- скорость прокрутки (для колеса)
	//	- разрешить прокрутку колесом
	$(selector).ddrScrollX(step, speed, mousewheel, ignore);
	
	
	
</script>