<?php namespace App\Models\Traits;

use App\Models\Collection\EloquentCollection;

trait Collectionable {
	/**
     * Создать новый экземпляр коллекции Eloquent.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = []) {
        return new EloquentCollection($models);
    }
}