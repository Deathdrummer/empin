@props([
	'id'    => 'list'.rand(0,9999999)
])


<ul
	{{$attributes->class(['ddrlist'])}}
	>{{$slot}}</ul>