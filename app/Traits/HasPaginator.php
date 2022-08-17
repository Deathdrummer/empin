<?php namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait HasPaginator {
	
	// трейту передать запрос без получения, то есть без get all или first
	
	
	/**
	 * @param QueryBuilder $query
	 * @return 
	 */
	public function paginate($query = null, $currentPage, $perPage, string $fields = '*') {
		if (!$query) return false;
		$fields = preg_split('/[\s,|]+/', $fields);
		$countAll = $query->count();
		$list = $query->select($fields)->forPage($currentPage, $perPage)->get();
		
		$pagination = new LengthAwarePaginator(
			$list,
			$countAll,
			$perPage,
			$currentPage,
		);
		
		if (!$allData = $pagination->toArray()) return false;
		
		return collect([
			'current_page'	=> $allData['current_page'], // Текущая страница
			'data'			=> $allData['data'], // данные (список)
			'first_item'	=> $allData['from'], // глобальный порядковый номер первого элемента списка в рамках страницы
			'last_item'		=> $allData['to'], // // глобальный порядковый номер последнего элемента списка в рамках страницы
			'last_page'		=> $allData['last_page'], // номер последней страницы
			'per_page'		=> $allData['per_page'], // кол-во записей в одной странице
			'total'			=> $allData['total'] // общее количество всех записей
		]);
	}
	
	
}