<?php namespace App\Exports;

use App\Services\Business\Contract;
use App\Traits\Settingable;
use Maatwebsite\Excel\Concerns\FromArray;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithBackgroundColor;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;


class ContractsExport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements FromArray, WithStyles, WithEvents, WithProperties, WithTitle/*, WithColumnWidths, WithDefaultStyles, WithBackgroundColor */ {
		
	use Settingable;
	
	private $colums = [];
	private $contractsList = [];
	private $columsNames = [];
	
	
	
	public function __construct($params) {
		[
			'contracts_ids' => $contractsIds,
			'selection_id' 	=> $selectionId,
			'colums' 		=> $colums,
			'sort'			=> $sort,
			'order'			=> $order,
		] = array_replace_recursive([
			'contracts_ids' => null,
			'selection_id' 	=> null,
			'colums' 		=> null,
			'sort'			=> null,
			'order'			=> null,
		], $params);
		
		$this->contract = app()->make(Contract::class);
		
		$this->colums = $colums;
        
		
		if ($contractsIds) $this->contractsList = $this->contract->getToExport($contractsIds, $colums, $sort, $order);
		elseif($selectionId) $this->contractsList = $this->contract->getSelectionToExport($selectionId, $colums);
		$this->columsNames = $this->contract->getColumsMap($colums);
    }
	
	
	
	
	
	/** Задать аназвание вкладки
     * @return string
     */
    public function title(): string {
        return 'Список договоров';
    }
	
	
	
	
	
	/**
    * @return \Illuminate\Support\Array
    */
    public function array(): array {
		return [
			$this->columsNames,
			$this->contractsList,
		];
    }
	
	
	
	
	
	
	
	public function properties(): array {
        return [
            'creator'        => 'Empin',
            'title'          => 'Empin договоры',
            'description'    => 'Список договоров',
            'company'        => 'Empin',
        ];
    }
	
	
	
	
	
	
	/**
     * @return array
     */
    public function registerEvents(): array {
        return [
            AfterSheet::class => [self::class, 'afterSheet'],          
        ];
    }
	
	
	public static function afterSheet(AfterSheet $event)  {
        $workSheet = $event->sheet->getDelegate();
		$workSheet->freezePane('A2');
    }
	
	
	
	
	
	/**
    * @return \Illuminate\Support\Array
    */
	public function styles(Worksheet $sheet): array {
		[$colsInfo, $lastColCoord] = $this->getColumsInfo();
		$lastRow = count($this->contractsList) + 1;
		
		
		
		// global
		
		//$sheet->getDefaultRowDimension()->setRowHeight(45);
		$sheet->getRowDimension('1')->setRowHeight(60);
		
		$sheet->getStyle('1:'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$sheet->getStyle('1:'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
		$sheet->getStyle('1:'.$lastRow)->getAlignment()->setWrapText(true);
		
		$sheet->getStyle('A1:'.$lastColCoord.'1')->getFont()->setSize(10)->setBold(true);
		$sheet->getStyle('A2:'.$lastColCoord.$lastRow)->getFont()->setSize(10);
		
		#d1d0d1
		
		$sheet->getStyle('A1:'.$lastColCoord.$lastRow)->applyFromArray([
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => ['argb' => 'FFD1D0D1'],
				],
			],
		]);
		
		
		
		/* $sheet->getStyle('A1:'.$lastColCoord.'1')
			->getBorders()
			->getAllBorders()
    		->setBorderStyle(Border::BORDER_THIN)
			->setColor('Black'); */
		
		/* $sheet->getStyle('A1:'.$lastColCoord.'1')
			->getFill()
    		->setFillType(Fill::FILL_SOLID)
    		->getStartColor()->setRGB('FEFFF1'); */
		
		
		// column
		foreach ($colsInfo as $field => ['width' => $width, 'type' => $type, 'column' => $column]) {
			
			$sheet->getColumnDimension($column)->setWidth($width);
			
			
			if ($type == 'date') {
				$sheet->getStyle($column.'2:'.$column.$lastRow)
					->getNumberFormat()
    				->setFormatCode('dd.mm.yyyy');
			
			} elseif ($type == 'price') {
				$sheet->getStyle($column.'2:'.$column.$lastRow)
					->getNumberFormat()
					->setFormatCode('#,##0.00_-"₽"');
			
			} elseif ($type == 'number') {
				$sheet->getStyle($column.'2:'.$column.$lastRow)
					->getNumberFormat()
					->setFormatCode('0');
			
			} elseif ($type == 'bool') {
				$sheet->getStyle($column.'2:'.$column.$lastRow)
					->getFont()
					->setSize(16)
					->setBold(true)
					->setName('Wingdings 2')
					->getColor()->setRGB('FF00B050');;
			
			} elseif ($type == 'percent') {
				$sheet->getStyle($column.'2:'.$column.$lastRow)
					->getNumberFormat()
					->setFormatCode('#,##0.00_-"%"');
			}
		}
		
		
		// rows
		for ($row = 2; $row <= $lastRow; $row++) {
			$sheet->getRowDimension($row)->setRowHeight(45);
		}
		
		
		return [
			1	=>	['fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FEFFF1'],
            ],
		]];
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//------------------------------------------------------------------------------------
	
	
	
	
	
	/** Сформировать массв даных о столбцах
	 * @param 
	 * @return 
	 */
	private function getColumsInfo() {
		$columsData = [
			'object_number'		=> ['width' => 10, 'type' => 'number'],
			'buy_number'		=> ['width' => 15, 'type' => 'number'],
			'without_buy'		=> ['width' => 10, 'type' => 'bool'],
			'title'				=> ['width' => 20, 'type' => 'text'],
			'applicant'			=> ['width' => 25, 'type' => 'text'],
			'titul' 			=> ['width' => 55, 'type' => 'text'],
			'contract' 			=> ['width' => 25, 'type' => 'text'],
			'subcontracting' 	=> ['width' => 10, 'type' => 'bool'],
			'gencontracting' 	=> ['width' => 10, 'type' => 'bool'],
			'customer' 			=> ['width' => 20, 'type' => 'text'],
			'locality' 			=> ['width' => 25, 'type' => 'text'],
			'date_send_action'	=> ['width' => 15, 'type' => 'date'],
			'count_ks_2'		=> ['width' => 10, 'type' => 'number'],
			'act_pir' 			=> ['width' => 10, 'type' => 'bool'],
			'price' 			=> ['width' => 15, 'type' => 'price'],
			'price_nds' 		=> ['width' => 15, 'type' => 'price'],
			'price_gen' 		=> ['width' => 15, 'type' => 'price'],
			'price_gen_nds' 	=> ['width' => 15, 'type' => 'price'],
			'price_sub' 		=> ['width' => 15, 'type' => 'price'],
			'price_sub_nds' 	=> ['width' => 15, 'type' => 'price'],
			'gen_percent' 		=> ['width' => 10, 'type' => 'percent'],
			'date_start' 		=> ['width' => 15, 'type' => 'date'],
			'date_end' 			=> ['width' => 15, 'type' => 'date'],
			'date_gen_start' 	=> ['width' => 15, 'type' => 'date'],
			'date_gen_end' 		=> ['width' => 15, 'type' => 'date'],
			'date_sub_start' 	=> ['width' => 15, 'type' => 'date'],
			'date_sub_end' 		=> ['width' => 15, 'type' => 'date'],
			'date_close' 		=> ['width' => 15, 'type' => 'date'],
			'date_buy' 			=> ['width' => 15, 'type' => 'date'],
			'hoz_method' 		=> ['width' => 10, 'type' => 'bool'],
			'type' 				=> ['width' => 15, 'type' => 'text'],
			'contractor' 		=> ['width' => 20, 'type' => 'text'],
			'archive_dir' 		=> ['width' => 30, 'type' => 'text'],
		];
		
		
		
		$intersectedData = [];
		foreach ($this->columsNames as $field => $name) {
			$intersectedData[$field] = $columsData[$field] ?? null;
		}
		
		
		$coordsIndex = 0;
		$lastColumnCoord = '';
		foreach ($intersectedData as $field => $info) {
			$coord = $this->getFieldCoords(++$coordsIndex);
			$intersectedData[$field]['column'] = $coord;
			$lastColumnCoord = $coord;
		}
		
		return [$intersectedData, $lastColumnCoord];
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param 
	 * @return 
	 */
	private function getFieldCoords($index = null) {
		if (is_null($index)) return false;
		
		$lettersStr = '|ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$lettersArr = str_split($lettersStr);
		unset($lettersArr[0]);
		
		$maskArr = [];
		
		$ceil = floor($index / 26);
		$remains = $index % 26; 
		
		if ($remains == 0) {
			$ceil = $ceil - 1;
			$remains = 26;
		}
		
		if ($ceil) $maskArr = str_split($ceil);
		$maskArr[] = $remains;
		
		$coordsStr = '';
		foreach ($maskArr as $idx) {
			$coordsStr .= $lettersArr[$idx];
		}
		
		return $coordsStr;
	}
	
	
	
	
}