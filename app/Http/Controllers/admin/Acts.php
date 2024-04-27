<?php namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Acts extends Controller {
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function import_to_export_acts(Request $request) {
		//toLog('import_to_export_acts');
		//$file = $request->file('template');
		$path = $request->template->store('acts_templates');
		
		//Storage::put('file.jpg', $contents);
		
		
		toLog($path);
	}
	
}

	







