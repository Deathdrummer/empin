<?php namespace App\Http\Controllers;

use App\Models\ContractFile;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ContractsFilesController extends Controller {
    
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
			'contract_id' 	=> 'required|numeric',
		]);
		
		$contractId = $validatedData['contract_id'];
		$fileNameOrig = $validatedData['filename_orig'];
		$isImage = $validatedData['is_image'];
		$size = $validatedData['size'];
		$file = $request->file('file');
		
		$uniqueFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
		
		if (!$path = $request->file('file')->storeAs("contracts/{$contractId}", $uniqueFileName)) return response()->json(null);
		
		//$fileNameSys = basename($path);
		
		$fromId = auth()->user()?->id ?? null;
		
		ContractFile::insert([
			'filename_orig' => $fileNameOrig,
			'filename_sys' 	=> $uniqueFileName,
			'contract_id' 	=> $contractId,
			'is_image' 		=> $isImage,
			'from_id' 		=> $fromId,
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
				$contractId = $decodeFileeData['contractId'];
				
				if (Storage::exists("contracts/{$contractId}/{$filenameSys}")) {
					$isDeleted = Storage::delete("contracts/{$contractId}/{$filenameSys}");
					if ($isDeleted) {
						$stat = ContractFile::where('filename_sys', $filenameSys)->delete();
					}
				}
				
				if (!$isDeleted) return response()->json(['is_deleted' => $isDeleted]);
			}
			
		} else {
			[
				'filename_sys'	=> $filenameSys,
				'contract_id'	=> $contractId,
			] = $request->validate([
				'filename_sys'	=> 'required|string',
				'contract_id'	=> 'required|numeric',
			]);
			
			if (Storage::exists("contracts/{$contractId}/{$filenameSys}")) {
				$isDeleted = Storage::delete("contracts/{$contractId}/{$filenameSys}");
				if ($isDeleted) {
					$stat = ContractFile::where('filename_sys', $filenameSys)->delete();
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
			'contract_id'	=> $contractId,
		] = $request->validate([
			'filename'		=> 'required|string',
			'contract_id'	=> 'required|numeric',
		]);
		
		
		if (Storage::exists("contracts/{$contractId}/{$filename}")) {
			return response()->download("storage/contracts/{$contractId}/{$filename}", null, ['x-export-filename' => urlencode('Название файла')])/* ->deleteFileAfterSend() */;
		}
		
		throw new Error('Ошибка export!');
		//return response()->json(['error' => 1]);
		
		
		//return response()->download('storage/'.$path, null, ['x-export-filename' => urlencode('Название файла')])/* ->deleteFileAfterSend() */;
	}
	
}