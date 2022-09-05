<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class ContractFilter extends AbstractFilter {
    public const ID = 'id';
    public const CONTRACT_ID = 'contract_id';
    public const ARCHIVE = 'archive';
    public const PIVOT = 'pivot';
    public const SEARCH = 'search';

    protected function getCallbacks(): array {
        return [
            self::ID => [$this, 'id'],
            self::CONTRACT_ID => [$this, 'id'],
            self::ARCHIVE => [$this, 'archive'],
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
			$query->orWhere('object_number', 'like', '%'.$value.'%');
			$query->orWhere('title', 'like', '%'.$value.'%');
			$query->orWhere('applicant', 'like', '%'.$value.'%');
			$query->orWhere('titul', 'like', '%'.$value.'%');
			$query->orWhere('contract', 'like', '%'.$value.'%');
		});
    }
	
	
}