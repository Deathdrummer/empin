<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use App\Traits\Settingable;
use Illuminate\Database\Eloquent\Builder;

class ContractFilter extends AbstractFilter {
	use Settingable;
	
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
		$settings = $this->getSettings([[
				'setting'	=> 'contract-customers:customers',
				'key'		=> 'id',
				'value'		=> 'name'
			], [
				'setting'	=> 'contract-types:types',
				'key'		=> 'id',
				'value'		=> 'title'
			], [
				'setting'	=> 'contract-contractors:contractors',
				'key'		=> 'id',
				'value'		=> 'name'
			]
		]);
		
		$customersMatch = $this->_subSearch($settings['customers'], $value);
		$typesMatch = $this->_subSearch($settings['types'], $value);
		$contractorsMatch = $this->_subSearch($settings['contractors'], $value);
		
		$builder->where(function (Builder $query) use($value, $customersMatch, $typesMatch, $contractorsMatch) {
			$query->orWhere('object_number', 'like', '%'.$value.'%');
			$query->orWhere('title', 'like', '%'.$value.'%');
			$query->orWhere('applicant', 'like', '%'.$value.'%');
			$query->orWhere('titul', 'like', '%'.$value.'%');
			$query->orWhere('contract', 'like', '%'.$value.'%');
			$query->orWhere('locality', 'like', '%'.$value.'%');
			$query->orWhere('buy_number', 'like', '%'.$value.'%');
			$query->orWhere('archive_dir', 'like', '%'.$value.'%');
			
			if ($customersMatch) $query->orWhereIn('customer', $customersMatch);
			if ($typesMatch) $query->orWhereIn('type', $typesMatch);
			if ($contractorsMatch) $query->orWhereIn('contractor', $contractorsMatch);
		});
    }
	
	
	
	
	
	
	/** Поиск по подстроке
	 * @param array  $data
	 * @param mixed  $value
	 * @return array
	 */
	private function _subSearch($data, $value): array {
		$keysToSearch = [];
		foreach ($data as $key => $str) {
			if (mb_stripos($str, $value) !== false) {
				$keysToSearch[] = $key;
			}
		}
		return $keysToSearch;
	}
	
	
}