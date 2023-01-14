<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use App\Traits\Settingable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ContractFilter extends AbstractFilter {
	use Settingable;
	
    public const ID = 'id';
    public const CONTRACT_ID = 'contract_id';
    public const ARCHIVE = 'archive';
    public const PIVOT = 'pivot';
    public const SEARCH = 'search';
    public const FILER = 'filter';

    protected function getCallbacks(): array {
        return [
            self::ID => [$this, 'id'],
            self::CONTRACT_ID => [$this, 'id'],
            self::ARCHIVE => [$this, 'archive'],
            self::PIVOT => [$this, 'pivot'],
            self::SEARCH => [$this, 'search'],
            self::FILER => [$this, 'filter'],
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
			
			// Поиск по JSON полю из relation таблицы
			$query->orWhereHas('info', function ($query) use ($value) {
				$query->where('data', 'like', '%'.$value.'%');
			});
			
			
			
			if ($customersMatch) $query->orWhereIn('customer', $customersMatch);
			if ($typesMatch) $query->orWhereIn('type', $typesMatch);
			if ($contractorsMatch) $query->orWhereIn('contractor', $contractorsMatch);
		});
    }
	
	
	
	
	
	
	public function filter(Builder $builder, $value) {
		['column' => $column, 'value' => $value] = json_decode($value, true);
		
		if (in_array($column, ['date_start', 'date_end', 'date_close'])) {
			$d = explode('|', $value);
			
			$dateFrom = $d[0] ?? null;
			$dateTo = $d[1] ?? null;
			
			if ($dateFrom) $builder->where($column, '>=', Carbon::parse($dateFrom));
			if ($dateTo) $builder->where($column, '<=', Carbon::parse($dateTo));
		
		} else {
			$builder->where($column, $value);
		}
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