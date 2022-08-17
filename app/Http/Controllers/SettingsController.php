<?php namespace App\Http\Controllers;

use App\Services\Settings;
use Illuminate\Http\Request;


class SettingsController extends Controller {
    
	protected $settings;
	
	public function __construct(Settings $settings) {
		$this->settings = $settings;
	}
	
	
	
	
	
	
	/**
	 * @param Request $request
	 * @return mixed 
	 */
	public function get(Request $request): mixed {
		['key' => $key] = $request->validate([
			'key'	=> 'required|string'
		]);
		
		if (!$key) return response()->json(false);
		$data = $this->settings->get($key);
		return response()->json($data);
	}
	
	
	
	
	
	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function set(Request $request): mixed {
		['group' => $group, 'key' => $key, 'value' => $value] = $request->validate([
			'group'	=> 'required|string',
			'key'	=> 'required|string',
			'value'	=> 'nullable'
		]);
		
		if (!$key) return response()->json(false);
		$data = $this->settings->set($group, $key, $value);
		return response()->json($data);
	}
	
	
	
	
	
	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function remove(Request $request): mixed {
		['path' => $path] = $request->validate([
			'path'	=> 'required|string'
		]);
		$stat = $this->settings->delete($path);
		return response()->json($stat);
	}
	
	
	
	
	
	
	
	
	
	
	
}