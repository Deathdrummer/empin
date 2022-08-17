<?php namespace App\Http\Filters\Base;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface {
    public function apply(Builder $builder);
}