<?php namespace App\Http\Controllers;

use App\Models\AssistentFile;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssistentFilesController extends Controller {
    /**
	* Загрузить файл на сервер
	* @param 
	* @return 
	*/
	public function upload(Request $request) {
		// Валидация данных
		$validatedData = $request->validate([
			'file'   		=> 'required|file', //'required|file|mimes:jpeg,png,pdf|max:2048', // Уточнил правила валидации файла
			'filename_orig'	=> 'required|string',
			'size' 			=> 'required|numeric',
			'is_image' 		=> 'required|boolean',
		]);
		
		$fileNameOrig = $validatedData['filename_orig'];
		$isImage = $validatedData['is_image'];
		$size = $validatedData['size'];
		$file = $request->file('file');
		
		$uniqueFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
		
		if (!$path = $request->file('file')->storeAs("assistent", $uniqueFileName)) return response()->json(null);
		
		AssistentFile::insert([
			'filename_orig' => $fileNameOrig,
			'filename_sys' 	=> $uniqueFileName,
			'is_image' 		=> $isImage,
			'size' 			=> $size,
			'upload_date'	=> Carbon::now(),
		]);
		
		return response()->json(['filename' => $uniqueFileName]);
	}
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function remove(Request $request) {
		$isDeleted = false;
		
		if ($request->has('files')) {
			$filesData = $request->input('files');
			foreach ($filesData as $fileData) {
				$decodeFileeData = json_decode($fileData, true);
				$filenameSys = $decodeFileeData['fileNameSys'];
				
				if (Storage::exists("assistent/{$filenameSys}")) {
					$isDeleted = Storage::delete("assistent/{$filenameSys}");
					if ($isDeleted) {
						$stat = AssistentFile::where('filename_sys', $filenameSys)->delete();
					}
				}
				
				if (!$isDeleted) return response()->json(['is_deleted' => $isDeleted]);
			}
			
		} else {
			[
				'filename_sys'	=> $filenameSys,
			] = $request->validate([
				'filename_sys'	=> 'required|string',
			]);
			
			if (Storage::exists("assistent/{$filenameSys}")) {
				$isDeleted = Storage::delete("assistent/{$filenameSys}");
				if ($isDeleted) {
					$stat = AssistentFile::where('filename_sys', $filenameSys)->delete();
				}
			}
		}
		
		return response()->json(['is_deleted' => $isDeleted]);
	}
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function download(Request $request) {
		[
			'filename'		=> $filename,
		] = $request->validate([
			'filename'		=> 'required|string',
		]);
		
		
		if (Storage::exists("assistent/{$filename}")) {
			return response()->download("storage/assistent/{$filename}", null, ['x-export-filename' => urlencode($filename)])/* ->deleteFileAfterSend() */;
		}
		
		throw new Error('Ошибка export!');
		//return response()->json(['error' => 1]);
		
		
		//return response()->download('storage/'.$path, null, ['x-export-filename' => urlencode('Название файла')])/* ->deleteFileAfterSend() */;
	}
}
