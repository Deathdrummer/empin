@aware([
	'settings' 	=> null,
	'groupWrap'	=> null,
])

@props([
	'id' 			=> 'input'.rand(0,9999999),
	'autocomplete'	=> 'off',
	'disabled'		=> false,
	'enabled'		=> true,
	'noedit'		=> false,
	'setting' 		=> false,
	'savedelay'		=> 500,
	'group' 		=> $groupWrap,
	'label' 		=> null,
	'showrows' 		=> false,
	'action'        => 'setSetting',
	'icon'        	=> null,
	'iconbg'     	=> null,
	'clearcolor'	=> null,
	'tag'        	=> null,
	'cleared'		=> null,
])



<div {{$attributes->class([
		'input',
		'cleared' => $cleared,
		$group.'-input' => $group,
		($group ? $group.'-' : '').'input-'.$type => $type,
		($group ? $group.'-' : '').'input_noempty' => !$cleared && $setValue($value, $settings, $setting),
		($group ? $group.'-' : '').'input_disabled' => $group && ($disabled || !$enabled),
		($group ? $group.'-' : '').'input_iconed' => $icon || $clearcolor,
	])}}>
	
	@if($label)
		<label
			@class([
				'input__label',
				$group.'-input__label' => $group,
				($group ? $group.'-' : '').'input__label-'.$type => $type,
				'noselect'
			])
			for="{{$id}}"
		 >{{$label}}</label>	
	@endif
	
	<input
		type="{{$type}}"
		@if($name)name="{{$name}}" @endif
		value="{{$setValue($value, $settings, $setting)}}"
		id="{{$id}}"
		@if($type != 'color')placeholder="{{$placeholder}}" autocomplete="{{$autocomplete}}" @endif
		@isset($group)inpgroup="{{$group}}" @endisset
		@if($disabled || !$enabled)disabled @endif
		@if($noedit)noedit @endif
		@if($setting)oninput="$.{{$action}}(this, '{{$setting}}', {{$savedelay}})" @endif
		@if(isset($actionFunc) && !$setting)oninput="$.{{$actionFunc}}(this{{isset($actionParams) ? ', '.$actionParams : null}})" @endif
		@if($showrows)showrows @endif
		{{$tag}}
		>
	
	@isset($clearcolor)
		<div
			clearcolor
			@class([
				'clearcolor',
				$group.'-clearcolor' => $group
			])
			onclick="$.clearColor{{$id}}(this)"
			><i class="fa-solid fa-ban" title="Очистить цвет"></i></div>
	@endif
	
	
	@if($type === 'password')
		<div showpassword @class([
			'showpassword',
			$group.'-showpassword' => $group
		])><i class="fa-solid fa-eye-slash" title="Показать пароль"></i></div>
	
	@elseif($icon)
		<div
			@class([
				'postfix_icon',
				'postfix_icon-hovered postfix_icon-colored' => $iconActionFunc && !$iconbg,
				'bg-'.$iconbg => $iconbg,
				'bg-'.$iconbg.'-hovered' => $iconbg && $iconActionFunc,
				'pointer' => $iconActionFunc
			])
			@if($iconActionFunc)onclick="$.{{$iconActionFunc}}(this{{$iconActionParams ? ', '.$iconActionParams : null}})" @endif
			><i class="fa-solid fa-{{$icon}}"></i></div>
	@endif
	
	<div class="{{($group ? $group.'-' : '').'input__errorlabel'}} noselect" errorlabel></div>
</div>



<script type="module">
	let clearcolor = '{{$clearcolor}}',
		input = $('#{{$id}}'),
		setting = '{{$setting}}';
	
	if (clearcolor) {
		let clearAction = 'clearColor{{$id}}';
		
		$[clearAction] = (clearbtn) => {
			$(input).removeAttrib('value');
			$(input).setAttrib('color', '#0000');
			$(input).trigger('input');
			$['setSetting'](input[0], setting);
			setTimeout(() => {
				$(input).removeAttrib('color');
			}, 300);
		}
	}
	
</script>