<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogController extends Controller {
	
	protected $logsDisk;
	
	public function __construct() {
		$this->logsDisk = Storage::disk('logs');
	}
    
	/**
	 * @param 
	 * @return 
	 */
	public function index() {
		//return view('logs');
		
		//$logFiles = $this->logsDisk->files();
		//array_splice($logFiles, array_search('.gitignore', $logFiles), 1);
		
		
		//$logData = $this->logsDisk->get('laravel-'.date('Y-m-d').'.log');
		$logData = $this->logsDisk->get('ddr.log');
		
		
		return view('logs', ['data' => $logData]);
		
	}
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function clear() {
		$this->logsDisk->put('ddr.log', '');
		return redirect('/logs');
	}
	
	
	
}