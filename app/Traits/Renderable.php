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
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function renderWithHeaders($view = null, $data = [], $headers = []) {
		if (!$this->renderPath) throw new \Exception('Renderable -> render: Ошибка! Не указан путь $renderPath');
		if (isset($this->data) && !empty($this->data)) $data = array_merge($data, $this->data);
		return response()
			->view($this->renderPath.'.'.$view, $data)
			->withHeaders($headers);
	}
}