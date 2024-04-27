<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadFilesController extends Controller {
    
	/**
	* 
	* @param 
	* @return 
	*/
	public function upload(Request $request) {
		$storage = $request->input('storage', '/');
		$path = $request->file->store($storage);
		return response()->json(['path' => $path]);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function remove(Request $request) {
		$path = $request->input('path');
		$isDeleted = Storage::delete($path);
		return response()->json(['is_deleted' => $isDeleted]);
	}
}
