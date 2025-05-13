<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromptFilesController extends Controller {
	/**
	* 
	* @param 
	* @return 
	*/
	public function save(Request $request) {
		[
			'filename'	=> $filename,
			'content'	=> $content,
		] = $request->validate([
			'filename'	=> 'required|string',
			'content'	=> 'nullable|string',
		]);
		
		$stat = Storage::put("prompts/{$filename}", $content ?? '');
		
		return response()->json(true);
	}
}
