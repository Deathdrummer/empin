@aware([
	'space'	=> null,
])

<div {{$attributes->class([
	'horisontal__item',
	'ml'.$space => $space
	])}}>{{$slot}}</div>