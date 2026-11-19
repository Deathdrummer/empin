<?php

namespace App\Production\Turv;

use App\Production\Support\ProductionException;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Выгрузка ТУРВ в XLSX и PDF (П-СМ-35): ровно то, что на экране — разделы, строки, доступные даты, порядок. Файл
 * строится из уже собранной таблицы месяца, поэтому изменения после сборки в него не попадают (инт. 8.57)
 */
final class TurvExport
{
    private const MONTHS = ['', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];

    public static function pdfAvailable(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    /** @return array{0: string, 1: string, 2: string} содержимое, имя файла, тип */
    public function build(array $table, string $format): array
    {
        if ($format === 'pdf' && !self::pdfAvailable()) {
            throw ProductionException::invalid('Выгрузка в PDF недоступна', 'pdf_unavailable');
        }
        $month = CarbonImmutable::createFromFormat('!Y-m', $table['month']);
        $title = 'Табель — '.self::MONTHS[$month->month].' '.$month->year;
        $book = new Spreadsheet();
        if ($format === 'pdf') {
            // Шрифт с кириллицей, который есть в dompdf: шрифт Excel по умолчанию dompdf не знает
            $book->getDefaultStyle()->getFont()->setName('DejaVu Sans')->setSize(9);
        }
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Табель');
        $count = count($table['dates']);
        $last = Coordinate::stringFromColumnIndex($count + 2);

        $book->getProperties()->setTitle($title)->setCreator('ЭМПИН');
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$last}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Сотрудник');
        foreach ($table['dates'] as $i => $date) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2).'2', $date['day']);
        }
        $sheet->setCellValue($last.'2', 'Итого');
        $sheet->getStyle("A2:{$last}2")->getFont()->setBold(true);

        $r = 3;
        foreach ($table['sections'] as $section) {
            $sheet->setCellValue("A$r", $section['title']);
            $sheet->mergeCells("A$r:{$last}$r");
            $sheet->getStyle("A$r")->getFont()->setBold(true);
            $sheet->getStyle("A$r:{$last}$r")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EDEDED');
            $r++;
            if ($section['empty']) {
                $sheet->setCellValue("A$r", 'Нет сотрудников за выбранный период');
                $sheet->mergeCells("A$r:{$last}$r");
                $r++;
                continue;
            }
            foreach ($section['rows'] as $row) {
                $sheet->setCellValue("A$r", $row['name']);
                foreach ($row['cells'] as $i => $cell) {
                    $coordinate = Coordinate::stringFromColumnIndex($i + 2).$r;
                    // «?» — служебный знак экрана для права 18, в файл не попадает: в файле только «Р», пусто и «—»
                    if ($cell === TurvService::CELL_DAY) {
                        $sheet->setCellValue($coordinate, 'Р');
                    } elseif ($cell === TurvService::CELL_LOCKED) {
                        $sheet->setCellValue($coordinate, '—');
                        $sheet->getStyle($coordinate)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
                        $sheet->getStyle($coordinate)->getFont()->getColor()->setRGB('808080');
                    }
                }
                $sheet->setCellValue($last.$r, $row['total']);
                $r++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(34);
        for ($i = 2; $i <= $count + 1; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(4);
        }
        $sheet->getColumnDimension($last)->setWidth(7);
        $sheet->getStyle('B2:'.$last.($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:'.$last.($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');
        $sheet->freezePane('B3');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0);

        $name = 'Табель '.$month->format('Y-m').'.'.$format;
        $writer = IOFactory::createWriter($book, $format === 'pdf' ? 'Dompdf' : 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $content = (string) ob_get_clean();

        return [$content, $name, $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    }
}
