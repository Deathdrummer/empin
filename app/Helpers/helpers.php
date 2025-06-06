<?php

use App\Helpers\DdrDateTime;
use App\Services\Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Encoder\IdnAddressEncoder;


if (!function_exists('toLog')) {
    /**
     * DDR Логгер
     *
     * @param  mixed  $message
     * @param  array  $params
     * @return mixed
     */
    function toLog($message = null, $params = []): mixed {
        if (is_null($message)) {
            return app('log');
        }

        $humanDate = $params['humandate'] ?? false;

        // Рекурсивная обработка ресурсов
        $message = cleanResource($message);

        // Рекурсивная дата-обработка
        if (is_array($message)) {
            arrayWalkRecursive($message, $humanDate);
        } elseif ($message instanceof Carbon) {
            $message = $humanDate
                ? DdrDateTime::date($message, ['shift' => '-']) . ' в ' . DdrDateTime::time($message, ['shift' => '-']) . ' [Carbon]'
                : DdrDateTime::shift($message, 'UTC') . ' [Carbon]';
        }

        return app('log')->debug($message, $params['context'] ?? []);
    }

    /**
     * Преобразование ресурсов в массивы (рекурсивно)
     */
    function cleanResource(mixed $data): mixed {
		if ($data instanceof AnonymousResourceCollection) {
			return cleanResource($data->toResponse(app('request'))->getData(true));
		}

		if ($data instanceof JsonResource) {
			return cleanResource($data->toArray(app('request')));
		}

		if ($data instanceof Collection) {
			return $data->map(fn($item) => cleanResource($item))->all();
		}

		if ($data instanceof Model) {
			return cleanResource($data->toArray());
		}

		if (is_array($data)) {
			return array_map(fn($item) => cleanResource($item), $data);
		}

		return $data;
	}
}



if (!function_exists('arrayWalkRecursive')) {
		function arrayWalkRecursive(&$mess, $humanDate) {
		array_walk_recursive($mess, function (&$item, $key) use($humanDate) {
			if ($item instanceof Carbon) {
				if ($humanDate) {
					$item = DdrDateTime::date($item, ['shift' => '-']).' в '.DdrDateTime::time($item, ['shift' => '-']).' [Carbon]';
				} else {
					$item = DdrDateTime::shift($item, 'UTC').' [Carbon]';
				}
			} elseif ($item instanceof Illuminate\Support\Collection) {
				$item = $item->toArray();
				$item = arrayWalkRecursive($item, $humanDate);
			} elseif (is_numeric($item)) {
				//$item = strpos($item, '.') !== false ? (float)$item : (int)$item;
			}
		});
		return $mess;
	}
}




if (! function_exists('getDeclension')) {
	/**
     * @param string  $value
     * @param bool  $slug
     * @param bool  $glue
     * @return string
     */
	function getDeclension($number, $titles = []) {
		if (!$titles|| count($titles) < 3) return report('getRublesDeclension неверно переданы аргументы!');
		$cases = [2, 0, 1, 1, 1, 2];
		$case = ($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)];
		return $titles[$case] ?? null;
	}
}
	


if (! function_exists('numberToWords')) {
	/**
     * @param string  $value
     * @param bool  $slug
     * @param bool  $glue
     * @return string
     */
	function numberToWords($number, $titles = []) {
		$spelloutFormatter = new \NumberFormatter('ru_RU', \NumberFormatter::SPELLOUT);
		$spelloutString = $spelloutFormatter->format($number);
		
		return $spelloutString.' '.getDeclension($number, $titles);
	}
}









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





if (! function_exists('arrFindMaxValueInField')) {
	/**
	 * Найти максимальное значение в определённом поле массива
	 * @param array $array массив
	 * @param string $field поле
	 * @return 
	*/
	function arrFindMaxValueInField(array $array = null, string $field = null) {
		if (!$array || !$field) return false;
		$maxValue = null;

		foreach ($array as $item) {
			if (isset($item[$field])) {
				if ($maxValue === null || $item[$field] > $maxValue) {
					$maxValue = $item[$field];
				}
			}
		}

		return $maxValue;
	}
} 






if (! function_exists('splitString')) {
	/**
	 * Разбивает строку по заданному разделителя
	 * @param string $str строка
	 * @param array $separator разделитель
	 * @param array $strict строгий режим
	 * @return array|null
	*/
	function splitString(?string $str = null, ?string $separator = null, $strict = false): array|null {
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
	 * @param array|null $arr массив
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












if (!function_exists('arrGetIndexFromField')) {
	/**
	 * Возвращает индекс элеменa массива по указанному значению указанного поля элеменa массива (стар. getIndexFromFieldValue)
	 * @param массив
	 * @param поле
	 * @param значение
	 * @return индекс
	*/
	function arrGetIndexFromField($array = [], $field = null, $value = null) {
		if(is_null($array) || is_null($field) || is_null($value)) return false;
		$res = array_filter($array, function($val, $key) use($field, $value) {
			return (isset($val[$field]) && $val[$field] == $value);
		}, ARRAY_FILTER_USE_BOTH);
		
		if ($res && count($res) > 1) {
			$keys = [];
			while ($item = current($res)) {
				$keys[] = key($res);
				next($res);
			}
			return $keys;
		} elseif ($res && count($res) == 1) {
			return key($res);
		} else {
			return false;
		}
	}
}







if (!function_exists('arrMergeFields')) {
	/**
	 * Принимает ассоциативный массив и объединяет поля с использованием указанного разделителя
	 * @param array $inputArray  массив
	 * @param string $separator  разделитель
	 * @return array|null  индекс
	*/
	function arrMergeFields(array $inputArray, string $separator):array|null {
		if (!$inputArray) return null;
		
		$result = [];

		foreach ($inputArray as $item) {
			foreach ($item as $key => $value) {
				if (array_key_exists($key, $result)) {
					$result[$key] .= $separator . $value;
				} else {
					$result[$key] = $value;
				}
			}
		}

		return $result;
	}

}



if (!function_exists('arrGetFirstItem')) {
	/**
	 * Вернет первый элемент любого массива
	 * @param array $inputArray  массив
	 * @return mixed  первое значение массива
	*/
	function arrGetFirstItem(array|Collection|null $inputArray):mixed {
		if (!$inputArray) return null;
		if ($inputArray instanceof Collection) return $inputArray?->first();
 		return reset($inputArray);
	}
}
















//--------------------------------------------




if (! function_exists('getActionFuncName')) {
	/**
	 * 
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
	 * 
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
	 * 
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
		if (!$date || !$format) return '';
		echo now()->parse($date)->format($format);
	}
}




if (! function_exists('dateFormatterNew')) {
	function dateFormatterNew($date, $template) {
		// Если дата передана как строка, создаем объект DateTime
		if (is_string($date)) {
			$date = new DateTime($date);
		}

		// Массивы для месяцев и дней недели
		$monthsNominative = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
		$monthsGenitive = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
		$monthsShort = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
		$daysShort = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
		$daysFull = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];

		// Получение всех нужных элементов даты
		$yearFull = $date->format('Y');
		$yearShort = $date->format('y');
		$monthIndex = (int)$date->format('n') - 1; // индекс месяца (0-11)
		$day = (int)$date->format('j');
		$dayWithLeadingZero = $date->format('d');
		$hour = $date->format('H'); // часы в 24-часовом формате
		$minute = $date->format('i'); // минуты
		$weekdayIndex = (int)$date->format('w'); // индекс дня недели (0-6, где 0 — воскресенье)

		// Замена шаблонов на значения даты
		return str_replace(
			['{yyyy}', '{yy}', '{mmm}', '{mm}', '{m}', '{dd}', '{d}', '{ww}', '{w}', '{h}', '{i}'],
			[$yearFull, $yearShort, $monthsGenitive[$monthIndex], $monthsNominative[$monthIndex], $monthsShort[$monthIndex], $dayWithLeadingZero, $day, $daysFull[$weekdayIndex], $daysShort[$weekdayIndex], $hour, $minute],
			$template
		);
	}
}












if (! function_exists('setting')) {
	/**
	 * Получить значение настройки
	 * @param string|null  $setting настройка
	 * @return string|null
	*/
	function setting(?string $setting = null) {
		if (!$setting) return null;
		$settingsService = App::make(Settings::class);
		return $settingsService->get($setting);
	}
}
















//------------------------------------------------------------



if (! function_exists('encodeEmail')) {
	/**
	 * Конвертирует Email адрес из кириллицы в UTF-8
	 * @param string|null  $address
	 * @return string
	*/
	function encodeEmail(?string $address): string {
		if (!$address) return (string)$address;
		$encoder = new IdnAddressEncoder();
		return $encoder->encodeString($address);
	}
}




if (! function_exists('decodeEmail')) {
	/**
	 * Конвертирует Email адрес обратно в кириллицу
	 * @param string|null  $address
	 * @return string
	*/
	function decodeEmail(?string $address): string {
		if (!$address) return (string)$address;
		$i = strrpos($address, '@');
        if (false !== $i) {
            $local = substr($address, 0, $i);
            $domain = substr($address, $i + 1);
            $address = sprintf('%s@%s', $local, idn_to_utf8($domain, \IDNA_DEFAULT | \IDNA_USE_STD3_RULES | \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ | \IDNA_NONTRANSITIONAL_TO_ASCII, \INTL_IDNA_VARIANT_UTS46));
        }
        return (string)$address;
	}
}


