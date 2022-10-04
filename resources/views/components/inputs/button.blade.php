@aware([
	'groupWrap'		=> null,
	'groupRounded'	=> null,
	'groupPx'		=> null,
	'groupW'		=> null,
	'groupDisabled'	=> false,
	'groupVariant'	=> false,
])

@props([
    'id' 		=> null,
    'disabled' 	=> $groupDisabled,
    'enabled' 	=> true,
    'group'		=> $groupWrap,
    'variant'	=> $groupVariant,
    'rounded'	=> $groupRounded,
    'px'		=> $groupPx,
    'w'			=> $groupW,
    'title'		=> null,
    'animation'	=> null,
    'animationDuration'	=> '1s',
])

@if($groupWrap)<div class="col-auto">@endif
<div {{$attributes->filter(fn ($value, $key) => $key == 'class')->class([
		'button',
		$group.'-button' => $group,
		'button-'.$variant => $variant,
		($group ? $group.'-' : '').'button_rounded' => $rounded,
		($group ? $group.'-' : '').'button_disabled' => $group && ($disabled || !$enabled),
	])}}
	>
	<button
		@if($id)id="{{$id}}"@endif
		{{$attributes->filter(fn ($value, $key) => $key !== 'class')}}
		@if($disabled || !$enabled)disabled @endif
		@isset($group) inpgroup="{{$group}}" @endisset
		@isset($title)title="{{$title}}"@endisset
		@if($action)onclick="$.{{$action}}(this{{isset($actionParams) ? ', '.$actionParams : null}})"@endif
		@class([
			'noselect',
			'pl'.$px.'px' => $px,
			'pr'.$px.'px' => $px,
			'w'.$w => $w,
			$animation => $animation
		])
		style="--fa-animation-duration: {{$animationDuration}};"
		@if($tag) {!!$tag!!} @endif
		>{{$slot}}</button>
</div>
@if($groupWrap)</div>@endif