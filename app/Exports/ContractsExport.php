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


class ContractsExport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements FromArray, WithStyles, WithEvents/*, WithColumnWidths, WithDefaultStyles, WithBackgroundColor */ {
		
	use Settingable;
	
	private $colums = [];
	//private $contractsIds = [];
	private $contractsList = [];
	private $columsNames = [];
	
	
	
	public function __construct($contractsIds, $colums) {
		$this->contract = app()->make(Contract::class);
		
		$this->colums = $colums;
        //$this->contractsIds = $contractsIds;
        
		$this->contractsList = $this->contract->getToExport($contractsIds, $colums);
		$this->columsNames = $this->contract->getColumsMap($colums);
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
	
	
	
	
	
	
	
	
	
	
	
	/**
     * @return array
     */
    public function registerEvents(): array {
        return [
            // Handle by a closure.
            /* BeforeImport::class => function(BeforeImport $event) {
                $creator = $event->reader->getProperties()->getCreator();
            }, */
			
		   
            // Using a class with an __invoke method.
            //BeforeSheet::class => new BeforeSheetHandler(),
            
            // Array callable, refering to a static method.
            AfterSheet::class => [self::class, 'afterSheet'],
                        
        ];
    }
	
	
	public static function afterSheet(AfterSheet $event)  {
        $workSheet = $event->sheet->getDelegate();
		$workSheet->freezePane('A2'); // freezing here
    }
	
	
	
	
	
	/**
    * @return \Illuminate\Support\Array
    */
	public function styles(Worksheet $sheet): void {
		[$colsInfo, $lastColCoord] = $this->getColumsInfo();
		$lastRow = count($this->contractsList) + 1;
		
		
		
		// global
		$sheet->getDefaultRowDimension()->setRowHeight(45);
		
		$sheet->getStyle('1:'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$sheet->getStyle('1:'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
		$sheet->getStyle('1:'.$lastRow)->getAlignment()->setWrapText(true);
		
		$sheet->getStyle('A1:'.$lastColCoord.'1')->getFont()->setSize(10)->setBold(true);
		$sheet->getStyle('A2:'.$lastColCoord.$lastRow)->getFont()->setSize(10);
		
		
		$sheet->getStyle('A1:'.$lastColCoord.'1')
			->getBorders()
			->getAllBorders()
    		->setBorderStyle(Border::BORDER_THIN);
		
		$sheet->getStyle('A1:'.$lastColCoord.'1')
			->getFill()
    		->setFillType(Fill::FILL_SOLID)
    		->getStartColor()->setRGB('FEFFF1');
		
		
		
		
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
			'price' 			=> ['width' => 15, 'type' => 'price'],
			'price_nds' 		=> ['width' => 15, 'type' => 'price'],
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