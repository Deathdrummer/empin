<?php namespace App\Traits;

trait Renderable {
	
	/**
	 * @param 
	 * @return 
	 */
	private function render($view = null, $data = [], $mergeData = []) {
		if (!$this->renderPath) throw new \Exception('Renderable -> render: Ошибка! Не указан путь $renderPath');
		if (isset($this->data) && !empty($this->data)) $mergeData = array_merge($mergeData, $this->data);
		return view($this->renderPath.'.'.$view, $data, $mergeData);
	}
}