<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ContractsExport implements FromArray {
    
	private $data;
	
	public function __construct($data)
    {
        $this->data = $data;
    }
	
	
	/**
    * @return \Illuminate\Support\Array
    */
    public function array(): array {
        return [$this->data['colums']];
    }
}
