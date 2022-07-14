<?php

namespace DigraphCMS\Spreadsheets\CellWriters;

use PhpOffice\PhpSpreadsheet\Cell\Cell;

class LongTextCell extends AbstractCellWriter
{
    public function transformCell(Cell $cell)
    {
        $cell->setValue($this->value);
        $cell->getStyle()->getAlignment()->setWrapText(true);
        // set column to 50 width
        $worksheet = $cell->getWorksheet();
        $worksheet->getColumnDimension($cell->getColumn())
            ->setAutoSize(false)->setWidth(50);
        // try to autofit height as well
        $lines = explode("\n", $cell->getValue());
        $lineCount = 0;
        foreach ($lines as $line) {
            $lineCount += ceil((strlen($line) * 1.1) / 50);
        }
        $lineCount = max($lineCount, 1);
        $worksheet->getRowDimension($cell->getRow())
            ->setRowHeight(max(
                ($lineCount * 13) + 5,
                $worksheet->getRowDimension($cell->getRow())->getRowHeight()
            ));
    }
}
