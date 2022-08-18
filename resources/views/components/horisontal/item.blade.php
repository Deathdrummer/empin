@aware([
	'space'		=> null,
])


<div {{$attributes->class([
	'horisontal__item',
	'ml'.$space => $space,
	])}} horisontalitem>{{$slot}}</div>