<?php namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Settings {
	
	
	
	
	/**
	 * @param string  $key
	 * @return Collection|string|bool|null
	 */
	public function get(?string $key = null, $default = null): Collection|string|bool|null {
		if (!$key) return false;
		['key' => $keyToFind, 'path' => $path] = $this->parseKey($key);
		
		if (!$row = Setting::where('key', $keyToFind)->first()) return $default;
		$value = $row->value;
		
		if (is_null($path)) return is_array($value) ? collect($value) : $value;
		if (!$result = data_get($value, $path)) return $default;
		return is_array($result) ? collect($result) : $result;
	}
	
	
	
	
	
	/**
	 * @param array|string  $key
	 * @param array|string ...$keys
	 * @return Collection|array|null
	 */
	public function getMany(array|string $key, array|string ...$keys): Collection|array|null|bool {
		if (!$key) return false;
		
		$keys = collect([]);
		foreach (func_get_args() as $arg) {
			if (is_array($arg)) $keys->push(...$arg);
			else $keys->push($arg);
		}
		
		$settingsData = collect([]);
		foreach (Setting::lazy() as $setting) {
			if ($keys->search($setting->key) !== false) {
				$settingsData->put($setting->key, $setting->value);
			}
		}
		return $settingsData;
	}
	
	
	
	/**
	 * @param string  $group
	 * @return Collection
	 */
	public function getGroup($group = null) {
		if (!$group) return false;
		
		if (!$data = Setting::where('group', $group)->get()) return false;
		
		$data = $data->mapWithKeys(function ($item, $key) {
			return [$item['key'] => $item['value']];
		});
		
		return $data->all();
	}
	
	
	
	/**
	 * @param 
	 * @return Collection
	 */
	public function getAll() {
		if (!$result = Setting::all()) return false;
		
		$data = $result->mapWithKeys(function ($item, $key) {
		    return [$item['key'] => $item['value']];
		});
		return $data->all();
	}
	
	
	
	
	
	
	/**
	 * @param string  $group
	 * @param string  $key
	 * @param null|string|array  $value
	 * @return bool
	 */
	public function set(?string $group = null, ?string $key = null, mixed $value = false): bool {
		if (!$key) return false;
		
		if ($value === false) {
			$value = $key;
			$key = $group;
			$group = false;
		}
		
		['key' => $keyToFind, 'path' => $path] = $this->parseKey($key);
		
		$setting = Setting::firstOrNew(['key' => $keyToFind]);
		
		$settingValue = $setting->value;
		
		if ($path) $setting->value = json_encode(data_set($settingValue, $path, $value), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		else $setting->value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : $value;
		if ($group) $setting->group = $group;
		$stat = $setting->save();
		return $stat;
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param string $path
	 * @return bool
	 */
	public function delete(?string $path = null): bool {
		if (!$path) return false;
		['key' => $keyToFind, 'path' => $pathToFind] = $this->parseKey($path);
		
		$setting = Setting::where('key', $keyToFind)->first();
		$settingValue = $setting->value;
		
		if (!is_null($pathToFind)) {
			Arr::forget($settingValue, $pathToFind);
			
			if (is_array($settingValue) && empty($settingValue)) {
				return $setting->delete();
			}
			
			$setting->value = json_encode($settingValue, JSON_UNESCAPED_UNICODE);
			return $setting->save();
		} 
		
		return $setting->delete();
	}
	
	
	
	
	
	
	
	
	
	//-------------------------------------------------------------------------------
	
	
	
	
	
	
	/**
	 * @param 
	 * @return array 
	 */
	private function parseKey(?string $keyStr = null):bool|array {
		if (!$keyStr) return false;
		$expKey = explode('.', $keyStr);
		$key = array_shift($expKey);
		$path = count($expKey) ? implode('.', $expKey) : null;
		return [
			'key' => $key ?: null,
			'path' => $path
		];
	}
	
	
	
}