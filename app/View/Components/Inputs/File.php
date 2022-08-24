<?php namespace App\View\Components\Inputs;

use App\Traits\HasComponent;
use Illuminate\View\Component;
use Illuminate\Support\Str;

class File extends Component {
	use HasComponent;
	
	protected $imagesTypes;
	
	public $id;
	public $name;
	public $group;
	public $types;
	public $multiple;
	
	public $tag;
	public $tagParam;
	
	
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
		?string $id = null,
		string $name = '',
		?string $group = 'null',
		?string $types = null,
		bool $multiple = false,
		?string $tag = null
	) {
        $this->id = $id;
        $this->name = $name;
        $this->group = $group;
        $this->types = $types;
        $this->multiple = $multiple;
        $this->imagesTypes = collect(['images', 'jpeg', 'jpg', 'png', 'apng', 'gif', 'bmp', 'webp']);
		
		[$t, $tValue] = $this->buildTag($tag);
		$this->tag = $t ?? null;
		$this->tagParam = $tValue ?? null;
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