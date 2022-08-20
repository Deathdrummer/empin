<?php

use Illuminate\Support\Str;


if (! function_exists('translit')) {
    /**
     * @param string  $value
     * @param bool  $slug
     * @param bool  $glue
     * @return string
     */
    function translit(?string $value = null, bool $slug = false, ?string $glue = '-') {
        if (!$value) return false;
		$converter = array(
			'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
			'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
			'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
			'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
			'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
			'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
			'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
	
			'А' => 'A',    'Б' => 'B',    'В' => 'V',    'Г' => 'G',    'Д' => 'D',
			'Е' => 'E',    'Ё' => 'E',    'Ж' => 'Zh',   'З' => 'Z',    'И' => 'I',
			'Й' => 'Y',    'К' => 'K',    'Л' => 'L',    'М' => 'M',    'Н' => 'N',
			'О' => 'O',    'П' => 'P',    'Р' => 'R',    'С' => 'S',    'Т' => 'T',
			'У' => 'U',    'Ф' => 'F',    'Х' => 'H',    'Ц' => 'C',    'Ч' => 'Ch',
			'Ш' => 'Sh',   'Щ' => 'Sch',  'Ь' => '',     'Ы' => 'Y',    'Ъ' => '',
			'Э' => 'E',    'Ю' => 'Yu',   'Я' => 'Ya',
		);
 
		$value = strtr($value, $converter);
		
		if ($slug) return Str::slug($value, $glue);
		return $value;
    }
}




if (! function_exists('translitSlug')) {
    /**
     * @param string  $value
     * @param string  $glue
     * @return string
     */
    function translitSlug(?string $value = null, ?string $glue = '-') {
        if (!$value) return false;
		return translit($value, true, $glue);
    }
}








if (! function_exists('arrTakeItem')) {
	/**
	 * Извлекает элемент из массива, сокращая сам массив
	 * @param array $arr массив
	 * @param ключ массива
	 * @param искать в значениях
	 * @param также будут проверяться типы
	 * @return 
	*/
	function arrTakeItem(&$arr = false, $itemKeyOrVal = false, $isValue = false, $strict = false) {
		if (!$arr || !$itemKeyOrVal) return false;
		if ($isValue) {
			if (($key = array_search($itemKeyOrVal, $arr, $strict)) === false) return false;
			$takeItem = $arr[$key];
			unset($arr[$key]);
			return $takeItem;
		} else {
			if (!array_key_exists($itemKeyOrVal, $arr)) return false;
			$takeItem = $arr[$itemKeyOrVal];
			unset($arr[$itemKeyOrVal]);
			return $takeItem;
		} 
		return false;
	}
} 



if (! function_exists('splitString')) {
	/**
	 * Разбивает строку по заданному разделителя
	 * @param array $arr массив
	 * @param array $separator разделитель
	 * @return array|null
	*/
	function splitString(?string $str = null, $separator, $strict = false): array|null {
		if (is_null($str)) return null;
		$res = preg_split('/\s*\\'.$separator.'\s*/', $str);
		if (!$strict) return $res ?: null;
		foreach ($res as $k => $item) {
			if (is_numeric($item)) $res[$k] = strpos($item, '.') ? (float)$item : (int)$item;
			elseif ($item == 'null' || $item == 'NULL') $res[$k] = null;
			elseif ($item == 'false' || $item == 'FALSE') $res[$k] = false;
			elseif ($item == 'true' || $item == 'TRUE') $res[$k] = true;
		}
		return $res ?: null;
	}
}






if (! function_exists('pregSplit')) {
	/**
	 * Разбивает строку по разделителям: пробел , ; |
	 * @param array $arr массив
	 * @param array $separator разделитель
	 * @return array|null
	*/
	function pregSplit(?string $str = null): array|null {
		if (is_null($str)) return null;
		return preg_split('/\s*[,|]\s*|\s*[;]\s*|\s+/', $str) ?: null;
	}
}





if (!function_exists('isJson')) {
    /**
     * Является ли формат строки JSON
     * @param строка
     * @return bool
    */
    function isJson($string) {
        if (is_array($string) || !is_string($string) || is_numeric($string) || is_integer($string) || is_bool($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}






















//--------------------------------------------




if (! function_exists('getActionFuncName')) {
	/**
	 * Извлекает элемент из массива, сокращая сам массив
	 * @param array $arr массив
	 * @param ...$addict  дополнительные параметры
	 * @return string|null
	*/
	function getActionFuncName(?string $actionString = null) {
		if (!$actionString) {
			echo null;
			return false;
		} 
		
		$actData = explode(':', $actionString);
		echo array_shift($actData) ?? null;
	}
}






if (! function_exists('buildAction')) {
	/**
	 * Извлекает элемент из массива, сокращая сам массив
	 * @param array $arr массив
	 * @param ...$addict  дополнительные параметры
	 * @return string|null
	*/
	function buildAction(?string $actionString = null, ...$addict) {
		if (!$actionString) {
			echo null;
			return false;
		} 
		
		$actData = explode(':', $actionString);
		$action = array_shift($actData) ?? null;
		$params = implode(':', $actData) ?? null;
		
		$paramsStrData = [];
		
		if ($addict) {
			foreach ($addict as $ad) {
				$ad = trim($ad);
				if ($ad === '') $ad = 'null';
				$paramsStrData[] = (is_numeric($ad) || in_array($ad, ['null', 'false', 'true'])) ? $ad : "'".$ad."'";
			}
		}
		
		if (isset($params) && ($splitParams = splitString($params, ','))) {
			foreach ($splitParams as $param) {
				$param = trim($param);
				if ($param === '') $param = 'null';
				$paramsStrData[] = (is_numeric($param) || in_array($param, ['null', 'false', 'true'])) ? $param : "'".$param."'";
			}
		}
		
		if ($paramsStrData) $onclick = '$.'.$action.'(this, '.implode(', ', $paramsStrData).')';
		else $onclick = '$.'.$action.'(this)';
		
		echo 'onclick="'.$onclick.'"';
	}
}








if (! function_exists('buildActionParams')) {
	/**
	 * Извлекает элемент из массива, сокращая сам массив
	 * @param array $arr массив
	 * @return string|null
	*/
	function buildActionParams(?string $actionString = null) {
		if (!$actionString) {
			echo null;
			return false;
		} 
		
		$actData = explode(':', $actionString);
		$action = array_shift($actData) ?? null;
		$params = implode(':', $actData) ?? null;
		
		$paramsStrData = [];
		if (isset($params) && ($splitParams = splitString($params, ','))) {
			foreach ($splitParams as $param) {
				$param = trim($param);
				if ($param === '') $param = 'null';
				$paramsStrData[] = (is_numeric($param) || in_array($param, ['null', 'false', 'true'])) ? $param : "'".$param."'";
			}
		}
		
		$onclickParams = null;
		if ($paramsStrData) $onclickParams = implode(', ', $paramsStrData);
		
		echo ', '.$onclickParams;
	}
}












if (! function_exists('dateFormatter')) {
	/**
	 * Конвертирует дату в формат по правилам Carbon
	 * @param string|null  $date строка даты
	 * @param string|null  $format формат
	 * @return string|null
	*/
	function dateFormatter(?string $date = null, ?string $format = null) {
		if (!$date || !$format) exit(null);
		echo now()->parse($date)->format($format);
	}
}
