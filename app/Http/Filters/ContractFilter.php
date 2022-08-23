<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class ContractFilter extends AbstractFilter {
    public const ID = 'id';
    public const CONTRACT_ID = 'contract_id';
    public const ARCHIVE = 'archive';
    //public const DEPARTMENT_ID = 'department_id';
    //public const HIDE = 'hide';
    //public const SHOW = 'show';
    public const PIVOT = 'pivot';
    public const SEARCH = 'search';

    protected function getCallbacks(): array {
        return [
            self::ID => [$this, 'id'],
            self::CONTRACT_ID => [$this, 'id'],
            self::ARCHIVE => [$this, 'archive'],
            //self::DEPARTMENT_ID => [$this, 'departmentId'],
           	//self::HIDE => [$this, 'hide'],
            //self::SHOW => [$this, 'show'],
            self::PIVOT => [$this, 'pivot'],
            self::SEARCH => [$this, 'search'],
        ];
    }
	
	
	
	
	public function id(Builder $builder, $value) {
        $builder->where('id', $value);
    }
	
	
	
	
    public function archive(Builder $builder, $value) {
        $builder->where('archive', $value);
    }
	
	
	//public function departmentId(Builder $builder, $value) {
	//	$values = splitString($value, ',', true);
	//	$firstValue = array_shift($values);
	//	
	//	$builder->whereRelation('departments', function(Builder $query) use($firstValue) {
	//		$query->where('department_id', $firstValue)
	//			->where('show', 1)
	//			->where('hide', 0);
	//	});
	//	
	//	
	//	/* $builder->whereRelation('departments', 'department_id', $firstValue);
	//	if ($values) {
	//		foreach ($values as $value) {
	//			$builder->orWhereRelation('departments', 'department_id', $value);
	//		}
	//	} */
    //}
	
	
	
	
	//public function hide(Builder $builder, $value) {
	//	$builder->whereRelation('departments', 'hide', $value);
	//}
	//
	//public function show(Builder $builder, $value) {
	//	$builder->whereRelation('departments', 'show', $value);
	//}
	
	
	
	
	
	
	
	/**
	 * @param Illuminate\Database\Eloquent\Builder  $builder
	 * @param array|string  $values
	 * @return 
	 */
	public function pivot(Builder $builder, $values) {
		if (isJson($values)) $values = json_decode($values, true);
		
		$builder->whereRelation('departments', function(Builder $query) use($values) {
			foreach ($values as $field => $value) {
				$query->where($field, $value);
			}
		});
	}
	
	
	
	
	public function search(Builder $builder, $value) {
		$builder->where(function (Builder $query) use($value) {
			$query->orWhere('title', 'like', '%'.$value.'%');
			$query->orWhere('titul', 'like', '%'.$value.'%');
			$query->orWhere('contract', 'like', '%'.$value.'%');
		});
    }
	
	
}