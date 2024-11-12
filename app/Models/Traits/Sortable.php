<?php namespace App\Models\Traits;

use App\Http\Filters\Base\FilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Sortable {
	/**
     * @param \Illuminate\Database\Eloquent\Builder  $builder
     * @param FilterInterface  $filter
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortByRelation(Builder $builder, Model $model = null, $condition = true, $sortField = null, $sortOrder = 'asc') {
		$builder->when($condition, function ($query) use($sortField, $sortOrder, $model) {
			$query->orderBy(
				$model::select($sortField)
				->whereColumn('contracts.id', 'contract_files.contract_id'),
				//->where('contracts.object_number', $sortField),
				$sortOrder 
			);
		});
			
    }
}