<?php namespace App\Http\Filters;

use App\Http\Filters\Base\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class DepartmentFilter extends AbstractFilter {
    public const DEPARTMENT_ID = 'department_id';
    public const ID = 'id';
    public const ASSIGNED_PRIMARY = 'assigned_primary';
    public const SHOW = 'show';
    public const HIDE = 'hide';

    protected function getCallbacks(): array {
        return [
            self::DEPARTMENT_ID => [$this, 'id'],
            self::ID => [$this, 'id'],
            self::ASSIGNED_PRIMARY => [$this, 'assignedPrimary'],
            self::SHOW => [$this, 'show'],
            self::HIDE => [$this, 'hide'],
        ];
    }
	
	
    public function id(Builder $builder, $value) {
        $builder->where('id', $value);
    }
	
	
	public function assignedPrimary(Builder $builder, $value) {
        $builder->where('assigned_primary', $value);
    }
	
	
	public function show(Builder $builder, $value) {
        $builder->whereRelation('contracts', 'show', $value);
    }
	
	
	public function hide(Builder $builder, $value) {
        $builder->whereRelation('contracts', 'hide', $value);
    }
	
	
	
}