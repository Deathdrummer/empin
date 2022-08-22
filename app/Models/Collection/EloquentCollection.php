<?php namespace App\Models\Collection;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentCollection extends Collection {
	
	/**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key / value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TModel, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
	 * @return \Illuminate\Support\Collection<TMapWithKeysKey, TMapWithKeysValue>|static<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeysMany(callable $callback) {
		$result = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                if (isset($result[$mapKey])) $result[$mapKey] = array_replace_recursive($result[$mapKey], $mapValue); 
				else $result[$mapKey] = $mapValue;
            }
        }

        $result = new static($result);

        return $result->contains(function ($item) {
            return ! $item instanceof Model;
        }) ? $result->toBase() : $result;
    }
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function test() {
		logger('eloquent test');
	}
}