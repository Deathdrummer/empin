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
		$path = $request->template->store('acts_templates');
	}
	
}

	







