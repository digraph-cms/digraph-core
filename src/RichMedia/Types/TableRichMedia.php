<?php

namespace DigraphCMS\RichMedia\Types;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use DigraphCMS\Digraph;

class TableRichMedia extends AbstractRichMedia
{
    public static function class(): string
    {
        return 'table';
    }

    public static function className(): string
    {
        return 'Table';
    }

    public function setTableFromFile(string $path, $extension = null)
    {
        switch ($extension) {
            case 'csv':
                $reader = ReaderEntityFactory::createCSVReader();
                break;
            case 'xlsx':
                $reader = ReaderEntityFactory::createXLSXReader();
                break;
            case 'ods':
                $reader = ReaderEntityFactory::createODSReader();
                break;
            default:
                $reader = ReaderEntityFactory::createReaderFromFile($path);
        }
        $reader->open($path);
        // loop through rows and cells in first sheet only, generating table data
        $data = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $r = [];
                foreach ($row->getCells() as $cell) {
                    $r[Digraph::uuid()] = $cell->getValue();
                }
                $data[Digraph::uuid()] = $r;
            }
            break; //break after first sheet
        }
        // overwrite existing table data
        unset($this['table']);
        $this['table'] = [
            'head' => [Digraph::uuid() => array_shift($data)],
            'body' => $data
        ];
    }

    public function render(): string
    {
        $html = '<table class="rich-media--table" data-table-id="' . $this->uuid() . '">';
        $html .= $this->renderGroup($this['table.head'], 'thead', 'th');
        $html .= $this->renderGroup($this['table.body'], 'tbody', 'td');
        $html .= '</table>';
        return $html;
    }

    protected function renderGroup(array $group, string $wrapTag, string $cellTag): string
    {
        $html = "<$wrapTag>";
        foreach ($group as $rowID => $row) {
            $html .= sprintf('<tr data-row-id="%s">', $rowID);
            foreach ($row as $cellID => $cell) {
                $html .= sprintf(
                    '<%s data-cell-id="%s">%s</%s>',
                    $cellTag,
                    $cellID,
                    $cell,
                    $cellTag
                );
            }
            $html .= '</tr>';
        }
        $html .= "</$wrapTag>";
        return $html;
    }
}
