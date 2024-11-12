<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use App\Traits\Settingable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ContractsFilesFilter extends AbstractFilter {
	use Settingable;
	
    public const FILTER = 'filter';
    public const SEARCH = 'search';

    protected function getCallbacks(): array {
        return [
            self::FILTER => [$this, 'filter'],
            self::SEARCH => [$this, 'search'],
        ];
    }
	
	
	
	public function filter(Builder $builder, $data) {
		if (!$data) return $builder;
		
		['column' => $column, 'items' => $items] = $data;
		
		if (Str::contains($column, '.')) {
			[$relationField, $relColumn] = Str::of($column)->split('/\./');
			
			return $builder->whereHas($relationField, function ($query) use($relColumn, $items) {
				$query->whereIn($relColumn, $items);
			})->when(in_array('', $items), function($q) use($relColumn) {
				$field = match($relColumn) {
					'object_number', 'applicant'	=> 'contract_id',
					'pseudoname'					=> 'from_id',
					default							=> false,
				};
				
				if ($field) $q->orWhereNull($field);
			});
			
			
		} elseif ($column == 'filetype') {
			$itemsString = implode("','", $items);
			return $builder->whereRaw("SUBSTRING_INDEX(filename_orig, '.', -1) IN ('{$itemsString}')");
			
		} else {
			return $builder->whereHas('contract', function ($query) use($column, $items) {
				$query->whereIn($column, $items);
			});
		}
	}
	
	
	
	
	
	/**
	* 
	* @param 
	* @return 
	*/
	public function search(Builder $builder, $search) {
		if (!$search) return $builder;
		
		$builder->whereHas('contract', function ($query) use($search) {
			$query->where('object_number', 'LIKE', "$search%");
		});
	}
	
}