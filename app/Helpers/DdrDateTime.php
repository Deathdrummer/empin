<?php namespace App\Helpers;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\App;

class DdrDateTime {
	
	
	/** Вывести дату в ЧПУ формате: 12 июля 2023 г.
	 * @param timestamp $timestamp
	 * @param array $shift, $locale, $isoFormat
	 * @return string|null
	 */
    public static function date($timestamp = null, $params = []):string|null {
		if (!$timestamp) return null;
		
		extract($params);
		
		$locale = $locale ?? App::currentLocale();
		
		$defaultFormat = match($locale) {
			'ru'		=> 'D MMMM YYYY г.',
			'ru_pure'	=> 'D MMMM YYYY',
			'en'		=> ['MMMM DD YYYY', 'Do MMMM'],
			default		=> 'D MMMM YYYY',
		};
		
		$format = match(true) {
			!isset($format) 	=> $defaultFormat,
			is_array($format) 	=> $format[$locale] ?? $defaultFormat,
			is_string($format) 	=> $format ?? $defaultFormat,
			default				=> $defaultFormat,
		};
		
		$buildShift = match(true) {
			$shift ?? false				=> config('app.shift_hours', 0),
			($shift ?? null) == '-'		=> (-1 * config('app.shift_hours', 0)),
			($shift ?? null) == '+'		=> config('app.shift_hours', 0),
			is_numeric($shift ?? null)	=> (int)$shift,
			default						=> null,
		};
		
		return self::buildCarbon([
			'timestamp' => $timestamp,
			'isoFormat'	=> $format,
			'shift' 	=> $buildShift,
			'locale' 	=> $locale,
		]);
    }
	
	
	
	/** Вывести время
	 * @param timestamp $timestamp
	 * @param array $shift, $locale, $format
	 * @return string|null
	 */
	 public static function time($timestamp = null, $params = []):string|null {
		if (!$timestamp) return null;
		
		extract($params);
		
		$locale = $locale ?? App::currentLocale();
		
		$defaultFormat = match($locale) {
			'ru'		=> 'H:i',
			'en'		=> 'h:i a',
			default		=> 'H:i',
		};
		
		$format = match(true) {
			!isset($format) 	=> $defaultFormat,
			is_array($format) 	=> $format[$locale] ?? $defaultFormat,
			is_string($format) 	=> $format ?? $defaultFormat,
			default				=> $defaultFormat,
		};
		
		$buildShift = match(true) {
			$shift ?? false				=> config('app.shift_hours', 0),
			($shift ?? null) == '-'		=> (-1 * config('app.shift_hours', 0)),
			($shift ?? null) == '+'		=> config('app.shift_hours', 0),
			is_numeric($shift ?? null)	=> (int)$shift,
			default						=> null,
		};
		
		return self::buildCarbon([
			'timestamp' => $timestamp,
			'format' 	=> $format,
			'shift' 	=> $buildShift,
			'locale' 	=> $locale,
		]);
    }
	
	
	
	
	
	
	/** Сформировать объект Carbon из строк даты и/или времени
	 * @param string $date
	 * @param string|array $time
	 * @param array $shift
	 * @return Carbon|null
	 */
	public static function buildTimestamp($date = null, $time = null, $params = []):Carbon|null {
		if (!$date) return null;
		
		if (is_array($time) && empty($params)) {
			$params = $time;
			$time = null;
		}
		
		extract($params);
		
		$timestamp = match(true) {
			isset($time) 	=> $date.($time ? ' '.$time : ''),
			!isset($time) 	=> $date,
			default			=> null,
		};
		
		$buildShift = match(true) {
			$shift ?? null				=> (-1 * config('app.shift_hours', 0)), // так как мы ориентируемся на UTC - от указанной даты мы наоборот, отномаем смещение
			($shift ?? null) == '-'		=> (-1 * config('app.shift_hours', 0)),
			($shift ?? null) == '+'		=> config('app.shift_hours', 0),
			is_numeric($shift ?? null)	=> (int)$shift,
			default						=> null,
		};
		
		return self::buildCarbon([
			'timestamp' => $timestamp,
			'shift' 	=> $buildShift,
			'locale' 	=> $locale ?? App::currentLocale(),
		]);
	}
	
	
	
	
	
	
	
	/** Сместить время объекта Carbon
	 * @param Carbon|string $timestamp
	 * @param integer $shift - UTC, TZ, [значение]
	 * @return Carbon|null
	 */
	public static function shift($timestamp = null, $shift = null):Carbon|null {
		if (!$timestamp) return null;
		
		$buildShift = match(true) {
			($shift ?? null) == 'UTC'		=> (-1 * config('app.shift_hours', 0)), // так как мы ориентируемся на UTC - от указанной даты мы наоборот, отномаем смещение
			($shift ?? null) == 'TZ'		=> config('app.shift_hours', 0),
			is_numeric($shift ?? null)	=> (int)$shift,
			default						=> null,
		};
		
		return self::buildCarbon([
			'timestamp' => $timestamp,
			'shift' 	=> $buildShift,
		]);
	}
	
	
	
	/** Вернуть текущее время объекта Carbon
	 * @return Carbon
	 */
	public static function now():Carbon {
		return Carbon::now();
	}
	
	
	
	
	
	
	public static function getOffsetDate(int $offsetDays = 0):Carbon {
    	return Carbon::now()->addDays($offsetDays);
	}
	
	
	
	
	
	
	
	/** Конвертировать формат даты
	 * @return String|null
	 */
	public static function convertDateFormat($dateTimeStr = null, $newFormat = 'd.m.y', $originalFormat = 'Y-m-d H:i:s'):String|null {
		if (!$dateTimeStr) return null;
		$dateTime = Carbon::createFromFormat($originalFormat, $dateTimeStr);
		return $dateTime->format($newFormat);
	}
	
	
	
	
	/** Конвертировать формат даты
	 * @return String|null
	 */
	public static function dateToHuman(?string $raw):?string {
		if (!$raw) return null;

		$date = Carbon::parse($raw)
			->locale('ru')
			->isoFormat('D MMMM YYYY');
		
		//toLog($date);
			
		return $date;
	}
		
	
	
	
	
	
	public static function dayOfWeek(Carbon|string $date, string $locale = 'ru'):string {
		$carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
		return $carbon->locale($locale)->isoFormat('dddd');
	}
	
	
	
	
	public static function numOfWeek(Carbon|string $date, string $locale = 'ru'):string {
		$carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
		return $carbon->dayOfWeekIso;
	}
	
	
	
	
	
	
	
	/** Проверить, является строка датой в соответствии с форматом
	 * @param timestamp $timestamp
	 * @param array $shift, $locale, $isoFormat
	 * @return string|null
	 */
	public static function isValidDateTime($dateTimeStr, $format = 'Y-m-d H:i:s'):bool {
		$dateTime = DateTime::createFromFormat($format, $dateTimeStr);
		return $dateTime && $dateTime->format($format) === $dateTimeStr;
	}
	
	
	
	
	
	
	
	//-----------------------------------------------
	
	
	
	/**
	 * @param array $timestamp, $shift, $locale, $format, $isoFormat
	 * @return Carbon|string|null
	 */
	static private function buildCarbon($params = []):Carbon|string|null {
		if (!$params) return null;
		
		extract($params);
		
		$carbon = match(true) {
			!isset($timestamp)		=> Carbon::now(),
			is_string($timestamp)	=> Carbon::parse($timestamp),
			is_object($timestamp)	=> Carbon::create($timestamp),
			default					=> Carbon::now(),
		};
		
		if (isset($shift)) $carbon = $carbon->addHours($shift);
		
		if (isset($locale)) $carbon = $carbon->locale($locale);
		
		if (isset($format)) $carbon = $carbon->format($format);
		elseif (isset($isoFormat)) {
			if (is_array($isoFormat)) $carbon = $carbon->isoFormat(...$isoFormat);
			else $carbon = $carbon->isoFormat($isoFormat);
		}
		
		return $carbon;
	}
	
	
}