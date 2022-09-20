<?php namespace App\Enums;

// https://sergeymukhin.com/blog/php-81-enums-perecisleniya
use Carbon\Carbon;

enum Period: string {
	case Year = 'year';
	case Month = 'month';
	case Week = 'week';
	
	
	/**
	 * @param 
	 * @return 
	 */
	public function date(): Carbon {
		return match($this) {
			static::Year => now()->startOfYear(),
			static::Month => now()->startOfMonth(),
			static::Week => now()->startOfWeek(),
			default => null
		};
	}
}