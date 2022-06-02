<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Digraph;
use DigraphCMS\RichContent\RichContent;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Ods;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class TableRichMedia extends AbstractRichMedia
{

    /**
     * Generate a shortcode rendering of this media
     *
     * @param ShortcodeInterface $code
     * @param self $table
     * @return string|null
     */
    public static function shortCode(ShortcodeInterface $code, $table): ?string
    {
        return $table->render();
    }

    public static function class(): string
    {
        return 'table';
    }

    public static function className(): string
    {
        return 'Table';
    }

    public static function description(): string
    {
        return 'Rich editor for tables, including the option to upload a spreadsheet';
    }

    public function setTableFromFile(string $path, $extension = null)
    {
        switch ($extension) {
            case 'csv':
                $reader = new Csv;
                break;
            case 'xlsx':
                $reader = new Xlsx;
                break;
            case 'xls':
                $reader = new Xls;
                break;
            case 'ods':
                $reader = new Ods;
                break;
            default:
                $reader = IOFactory::createReaderForFile($path);
        }
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        // loop through rows and cells in first sheet only, generating table data
        $sdata = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $data = [];
        foreach ($sdata as $rid => $row) {
            $r = [];
            foreach ($row as $cid => $cell) {
                $r[] = [
                    'id' => Digraph::uuid(null, md5(serialize([$rid, $cid]))),
                    'cell' => $cell
                ];
            }
            $data[] = [
                'id' => Digraph::uuid(null, md5($rid)),
                'row' => $r
            ];
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
            $rowID = $row['id'];
            $row = $row['row'];
            $html .= sprintf('<tr data-row-id="%s">', $rowID);
            foreach ($row as $cell) {
                $cellID = $cell['id'];
                $cell = $cell['cell'];
                $html .= sprintf(
                    '<%s data-cell-id="%s">%s</%s>',
                    $cellTag,
                    $cellID,
                    new RichContent($cell),
                    $cellTag
                );
            }
            $html .= '</tr>';
        }
        $html .= "</$wrapTag>";
        return $html;
    }
}
