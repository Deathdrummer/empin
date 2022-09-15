@aware([
	'space'	=> 0
])

@props([
	
])


<li
	{{$attributes->class(['ddrlist__item', 'mt'.$space => $space])}}
	>{{$slot}}</li>