<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;

class ButtonsGroup extends Component {
    
	public $groupWrap;
    public $groupRounded;
    public $groupPx;
    public $groupW;
    public $gx;
    public $gy;
    public $groupDisabled;
    public $groupVariant;
    public $tag;
    
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        ?string $group = null,
		?int $gx = null,
		?int $gy = null,
		?int $px = null,
		?string $w = null,
		?bool $disabled = false,
		?bool $rounded = null,
		?string $variant = null,
        ?string $tag = null,
		) {
        $this->groupWrap = $group;
        $this->groupRounded = isset($rounded);
        $this->groupPx = $px;
        $this->groupW = $w;
        $this->gx = $gx;
        $this->gy = $gy;
        $this->groupDisabled = $disabled;
        $this->groupVariant = $variant;
        $this->tag = $tag;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return <<<'blade'
			<div @class(['row', 'buttons-group', $groupWrap.'-buttons-group' => $groupWrap, 'gx-'.$gx => $gx !== null, 'gy-'.$gy => $gy !== null]) {{$tag}}>{{$slot}}</div>
		blade;
    }
}