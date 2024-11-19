<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use App\Models\ContractData;
use App\Models\ContractDepartment;
use App\Traits\Settingable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Business\User as UserService;
use Illuminate\Support\Str;

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
		
		$searchItems = array_filter(preg_split("/\s*\+\s*/", $value));
		
		if ($searchItems) {
			$user = app()->make(UserService::class);
			$dopsearchInfo = $user->getSettings('contracts.dopsearch.info');
			$dopsearchChats = $user->getSettings('contracts.dopsearch.chats');
			
			foreach ($searchItems as $search) {
				$customersMatch = $this->_subSearch($settings['customers'], $search);
				$typesMatch = $this->_subSearch($settings['types'], $search);
				$contractorsMatch = $this->_subSearch($settings['contractors'], $search);
			}
			
			$builder->where(function (Builder $query) use($searchItems, $customersMatch, $typesMatch, $contractorsMatch, $dopsearchInfo, $dopsearchChats) {
				foreach ($searchItems as $search) {
					$query->orWhere('object_number', 'like', '%'.$search.'%');
					$query->orWhere('title', 'like', '%'.$search.'%');
					$query->orWhere('applicant', 'like', '%'.$search.'%');
					$query->orWhere('titul', 'like', '%'.$search.'%');
					$query->orWhere('contract', 'like', '%'.$search.'%');
					$query->orWhere('locality', 'like', '%'.$search.'%');
					$query->orWhere('buy_number', 'like', '%'.$search.'%');
					$query->orWhere('archive_dir', 'like', '%'.$search.'%');
					
					// Поиск по доп. информации
					$query->when($dopsearchInfo, function ($query) use ($search) {
						$query->orWhereHas('info', function ($query) use ($search) {
							$query->whereRaw('data COLLATE utf8mb4_general_ci LIKE ?', ['%' . $search . '%']);
						});
					});
					
					// Поиск по чатам
					$query->when($dopsearchChats, function ($query) use ($search) {
						$query->orWhereHas('messages', function ($query) use ($search) {
							$query->whereRaw('message COLLATE utf8mb4_general_ci LIKE ?', ['%' . $search . '%']);
						});
					});
					
					if ($customersMatch) $query->orWhereIn('customer', $customersMatch);
					if ($typesMatch) $query->orWhereIn('type', $typesMatch);
					if ($contractorsMatch) $query->orWhereIn('contractor', $contractorsMatch);
				}
			});
		}
    }
	
	
	
	
	
	
	public function filter(Builder $builder, $value) {
		$filters = json_decode($value, true);
		
		$groupingFilters = [];
		foreach ($filters as $item) {
			if (!$item['column'] || !$item['value']) continue;
			$groupingFilters[$item['column']][] = $item['value'];
		}
		
		
		if (isset($groupingFilters['step'])) {
			$stepsGroups = [];
			foreach ($groupingFilters['step'] as $subArray) {
				$stepType = array_shift($subArray);
				$stepId = array_shift($subArray);
				$stepsGroups[$stepType][$stepId][] = reset($subArray);
			}
			$groupingFilters['step'] = $stepsGroups;
		}
		
		
		foreach ($groupingFilters as $column => $values) {
			$builder->where(function($query) use($column, $values) {
				if ($column == 'step') { #Все фильтры step
					
					$query->where(function($q) use($values) {
						foreach ($values as $stepType => $stepCols) {
							
							if ($stepType == 1) { #--- Если это чекбокс
								$q->where(function($qq) use($stepCols, $stepType) {
									foreach ($stepCols as $stepId => $stepItems) {
										$qq->where(function($qqq) use($stepType, $stepId, $stepItems) {
											foreach ($stepItems as $stepValue) {
												
													if ($stepValue == -1) { # если чекбокс отсутствует
														$qqq->orWhereNotIn(
															'id',
															ContractDepartment::select('contract_id')
																->whereJsonContains('steps', ['step_id' => $stepId]));
													} elseif ($stepValue == 0) { # если чекбокс неактивен
														$qqq->orWhereIn(
															'id',
															ContractDepartment::select('contract_id')
																->whereJsonContains('steps', ['step_id' => $stepId])
																->whereNotIn(
																	'contract_id',
																	ContractData::select('contract_id')
																		->where('step_id', $stepId)
																		->where('type', $stepType)
																		->where('data', 1)
																)
														);
													} elseif ($stepValue == 1) { # если чекбокс активен
														$qqq->orWhereIn(
															'id',
															ContractDepartment::select('contract_id')
																->whereJsonContains('steps', ['step_id' => $stepId])
																->whereIn(
																	'contract_id',
																	ContractData::select('contract_id')
																		->where('step_id', $stepId)
																		->where('type', $stepType)
																		->where('data', 1)
																)
														);
													}	
												
											}
										});
										
									} 	
								});
								
							} else if ($stepType == 3) { # Если это вып. список сотрудников
								$q->where(function($qq) use($stepType, $stepCols) {
									
									foreach ($stepCols as $stepId => $stepItems) {
										$qq->where(function($qqq) use($stepType, $stepId, $stepItems) {
											foreach ($stepItems as $stepValue) {
												$qqq->orWhereIn(
													'id',
													ContractData::select('contract_id')
														->where('step_id', $stepId)
														->where('type', $stepType)
														->when($stepValue !== -1, function($q) use($stepValue) {
															$q->where('data', $stepValue);
														}, function($q) {
															$q->whereNull('data');
														})
														
												);	
											}
										});
									}
								});
							}
						} 	
					});
					
				} else { # Остальные фильтры
					foreach ($values as $value) {
						if (preg_match('/^date_/', $column)) {
							$d = explode('|', $value);
							
							$dateFrom = $d[0] ?? null;
							$dateTo = $d[1] ?? null;
							
							if ($dateFrom) $query->where($column, '>=', Carbon::parse($dateFrom));
							if ($dateTo) $query->where($column, '<=', Carbon::parse($dateTo));
						
						} elseif ($column != 'step' && Str::contains($value, '|') ) {
							$d = explode('|', $value);
							
							$valFrom = $d[0] ?? null;
							$valTo = $d[1] ?? null;
							
							if ($valFrom) $query->where($column, '>=', $valFrom);
							if ($valTo) $query->where($column, '<=', $valTo);
						
						} else {
							if ($value == -1) {
								$query->orWhereNull($column);
							} else {
								$query->orWhere($column, $value);
							}
						}
					}
				}
			});
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