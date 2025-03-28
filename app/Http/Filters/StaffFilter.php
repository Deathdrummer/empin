<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use App\Traits\Settingable;
use Illuminate\Database\Eloquent\Builder;

class StaffFilter extends AbstractFilter {
	use Settingable;
	
    public const WORKING = 'working';
    public const REGISTRED = 'registred';
    public const DEPARTMENTS = 'departments';

    protected function getCallbacks(): array {
        return [
            self::WORKING => [$this, 'working'],
            self::REGISTRED => [$this, 'registred'],
            self::DEPARTMENTS => [$this, 'departments'],
        ];
    }
	
	
	
	
	public function working(Builder $builder, $value) {
        $builder->where('working', $value);
    }
	
	
	
	public function registred(Builder $builder, $value) {
		if ($value) {
			$builder->whereHas('registred');
		} else {
			$builder->whereDoesntHave('registred');
		}
    }
	
	
	public function departments(Builder $builder, $depsIds) {
		$builder->whereHas('registred', function ($q) use ($depsIds) {
			$q->whereIn('department_id', $depsIds);
		});
    }
	
	
	
	
}