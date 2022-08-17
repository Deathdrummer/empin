<?php namespace App\Models\Traits;

use App\Http\Filters\Base\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

trait Filterable {
	/**
     * @param \Illuminate\Database\Eloquent\Builder  $builder
     * @param FilterInterface  $filter
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter(Builder $builder, FilterInterface $filter) {
        $filter->apply($builder);
        return $builder;
    }
}