<?php namespace App\Http\Controllers;

use App\Http\Filters\ContractsFilesFilter;
use App\Models\ContractFile;
use App\Services\Settings;
use App\Traits\Renderable;
use App\Traits\Settingable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;

class UploadFilesController extends Controller {
	use Renderable, Settingable;
	
	protected $renderPath = 'admin.section.contracts.render.contracts_files';
	//protected $data = [];
	//protected $department;
	//protected $user;
	//protected $contracts;
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function list(Request $request, Settings $settings) {
		[
			'sort_field' 	=> $sortField,
			'sort_order' 	=> $sortOrder,
			'offset'		=> $offset,
		] = $request->validate([
			'sort_field' 	=> 'required|string',
			'sort_order' 	=> 'required|string',
			'offset'		=> 'required|numeric',
			'filter'		=> 'nullable|json',
			'search'		=> 'nullable|string',
		]);
		
		$filter = $request->input('filter', null);
		$search = $request->input('search', null);
		
		
		$files = null;
		$limit = $settings->get('contract-files-part-count', 50);
		
		$filter = app()->make(ContractsFilesFilter::class, ['queryParams' => ['filter' => json_decode($filter, true), 'search' => $search]]);
		
		$contractfilesQuery = ContractFile::with(['contract', 'author']);
			
			
		
		$contractFilesCount = (clone $contractfilesQuery)->count();
		$contractFilesSizeSumm = (clone $contractfilesQuery)->sum('size');
		$contractFilesFiltredCount = (clone $contractfilesQuery)->filter($filter)->count();
		$contractFilesFiltredSizeSumm = (clone $contractfilesQuery)->filter($filter)->sum('size');
			
			
		$contractfiles = $contractfilesQuery->when(Str::contains($sortField, '.'), function ($query) use($sortField, $sortOrder) {
				[$localField, $tableName, $foreignField] = Str::of($sortField)->split('/\./');
				
				$className = Str::studly(Str::singular($tableName));
				
				if($model = app()->make('App\\Models\\'.$className)) {
					$query->orderBy(
						$model::select($foreignField)
						->whereColumn("{$tableName}.id", "contract_files.{$localField}"),
						//->where('contracts.object_number', $sortField),
						$sortOrder 
					);
				}
				
			}, function($query) use($sortField, $sortOrder) {
				if ($sortField == 'filetype') {
					$query->orderByRaw("SUBSTRING_INDEX(filename_orig, '.', -1) {$sortOrder}");
				} else {
					$query->orderBy($sortField, $sortOrder);
				}
			})
			->filter($filter)
			->limit($limit)
			->offset($offset * $limit)
			->get();
			
			toLog($contractfiles);
		
		if (!$contractfiles) return response()->json(false);
		
		
		$files = $contractfiles->filter(function($row) {
			$path = 'contracts/'.$row['contract_id'].'/'.$row['filename_sys'];
			if (!Storage::exists($path)) {
				$row['thumb'] = "/assets/images/none.png";
				return true;
			} 
			
			if (!$row['is_image']) {
				$extension = File::extension($path);
				$row['thumb'] = "/assets/images/filetypes/{$extension}.png";
				return true;
			} 
			
			$image = Image::read('storage/'.$path);
			$thumb = $image->scale(height: 70);
			$row['thumb'] = $thumb->toGif()->toDataUri();
			return true;
		});
		
		
		$headers = [
			'x-count-items' 			=> $contractFilesCount,
			'x-count-filtred-items' 	=> $contractFilesFiltredCount,
			'x-count-size-summ' 		=> $contractFilesSizeSumm,
			'x-count-filtred-size-summ' => $contractFilesFiltredSizeSumm,
		];
		
		$itemView = $this->renderPath.'.row';
		
		return $this->renderWithHeaders(
			'list',
			compact(
				'files',
				'itemView'
			),
			$headers
		);
	}
	
	
	
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function get_values_to_filter(Request $request) {
		[
			'column'	=> $column,
		] = $request->validate([
			'column'	=> 'required|string',
		]);
		
		$list = match(true) {
			in_array($column, ['filename_orig', 'size', 'upload_date'])	=> ContractFile::query()->pluck($column)->unique()->values(),
			
			$column == 'filetype'	=> ContractFile::query()
				->pluck('filename_orig')
				->map(function ($filename) {
					return pathinfo($filename, PATHINFO_EXTENSION);
				})
				->unique()
				->values(),
			
			//in_array($column, ['contract.object_number', 'contract.applicant', 'contract.archive'])	=> ContractFile::with(['contract'])->get()->pluck($column)->flatten()->unique(),
			Str::contains($column, '.')	=> ContractFile::with(Str::of($column)->split('/\./')->get(0))->get()->pluck($column)/* ->flatten() */->unique(),
			//in_array($column, ['pseudoname'])	=> ContractFile::with(['author'])->get()->pluck('author.'.$column)->unique(),
			
			default	=> null,
		};
		
		
		/* $query->orderBy(
						$model::select($foreignField)
						->whereColumn("{$tableName}.id", "contract_files.{$localField}"),
						//->where('contracts.object_number', $sortField),
						$sortOrder 
					); */
		
		
		
		/* $list = ContractFile::query()
			->pluck('filename_orig') // Извлекаем все значения столбца filename
			->map(function ($filename) {
				return pathinfo($filename, PATHINFO_EXTENSION); // Извлекаем расширение файла
			})
			->unique() // Оставляем только уникальные значения
			->values(); // Сбрасываем ключи (опционально)
 */
		return $this->render('filter_list', compact('list', 'column'));
	}
	
	
	
	
	
	
	
	
	
	
	
	
    
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
