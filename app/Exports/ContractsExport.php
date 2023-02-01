<?php

namespace App\Exports;

use App\Services\Business\Contract;
use Maatwebsite\Excel\Concerns\FromArray;

class ContractsExport implements FromArray {
    
	private $data;
	private $contract;
	
	public function __construct($data) {
        $this->data = $data;
		$this->contract = app()->make(Contract::class);
    }
	
	
	/**
    * @return \Illuminate\Support\Array
    */
    public function array(): array {
		$data = $this->contract->getToExport($this->data);
		$columsNames = $this->contract->getColumsMap();
		
		$colNames = array_intersect_key($columsNames, array_flip($this->data['colums']));
		
		return [
			array_values($colNames),
			$data
		];
    }
}
