<?php namespace App\View\Components\Inputs;

use Illuminate\View\Component;
use Illuminate\Support\Str;

class File extends Component {
	
	protected $imagesTypes;
	
	public $id;
	public $name;
	public $group;
	public $types;
	public $multiple;
	
	
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(?string $id = null, string $name = '', ?string $group = 'null', ?string $types = null, bool $multiple = false) {
        $this->id = $id;
        $this->name = $name;
        $this->group = $group;
        $this->types = $types;
        $this->multiple = $multiple;
        $this->imagesTypes = collect(['images', 'jpeg', 'jpg', 'png', 'apng', 'gif', 'bmp', 'webp']);
    }
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function isMultiple() {
		if ($this->multiple) return 'multiple';
		return '';
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setInpGroup() {
		if ($this->group) return $this->group ? "inpgroup={$this->group}" : '';
	}
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function setEmptyText() {
		$hasImgTypes = Str::of(Str::of($this->types))->contains($this->imagesTypes->all());
		if ($hasImgTypes) {
			return 'Нет картинки';
		}
		return 'Нет файла';
	}
	
	
	

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render() {
        return view('components.inputs.file');
    }
}