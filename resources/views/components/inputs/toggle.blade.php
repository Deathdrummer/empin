@aware([
    'settings'  => null,
    'groupWrap' => null,
])

@props([
    'checked'   => false,
    'id'        => 'toggle'.rand(0,9999999),
    'disabled'  => false,
    'setting'   => false,
    'group'     => $groupWrap,
    'label'     => null,
    'action'    => 'setSetting',
])


<label {{$attributes->class([
        'toggle',
        $group.'-toggle' => $group,
        ($group ? $group.'-' : '').'toggle_checked' => $checked ?: $setChecked($checked, $settings, $setting),
        ($group ? $group.'-' : '').'toggle_disabled' => $disabled,
        'toggle_disabled' => $disabled,
        ])}}>
    
    <input
        class="sr-only"
        type="checkbox"
        tagname="toggle"
        @if($name)name="{{$name}}" @endif
        @if($setting)oninput="$.setSetting(this, '{{$setting}}')" @endif
        id="{{$id}}"
        @checked($checked ?: $setChecked($checked, $settings, $setting)) 
        @if($disabled)disabled @endif
        @isset($group) inpgroup="{{$group}}" @endisset
        @if($setting)oninput="$.{{$action}}(this{{isset($actionParams) ? ', '.$actionParams : null}})"@endif
        @if($actionFunc && !$setting)oninput="$.{{$actionFunc}}(this{{isset($actionParams) ? ', '.$actionParams : null}})" @endif
        @if($tag) {!!$tag!!} @endif>
    
    <div class="toggle-switch">
        <span class="toggle-thumb"></span>
    </div>
</label>