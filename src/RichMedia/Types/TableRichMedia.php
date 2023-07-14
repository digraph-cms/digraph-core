<?php

namespace DigraphCMS\RichMedia\Types;

use DigraphCMS\Digraph;
use DigraphCMS\FS;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\RadioListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TableInput;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTML\Icon;
use DigraphCMS\Media\DeferredFile;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Spreadsheets\SpreadsheetWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Ods;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class TableRichMedia extends AbstractRichMedia
{

    public function icon()
    {
        return new Icon('table');
    }

    protected function prepareForm(FormWrapper $form, $create = false)
    {
        // name input
        $name = (new Field('Name'))
            ->setDefault($this->name())
            ->setRequired(true)
            ->addForm($form);

        // toggle field for choosing whether to edit manually or upload spreadsheet
        $mode = (new RadioListField('How would you like to enter the table\'s content?', [
        'edit' => 'Edit table content manually',
        'file' => 'Upload a spreadsheet'
        ]))
            ->setRequired(true)
            ->setDefault('edit')
            ->addForm($form);

        // manual table editing field
        $table = (new Field('Table content', new TableInput()))
            ->setDefault($this['table'])
            ->setID('rich-table-edit-field')
            ->addForm($form);

        // export tool
        if ($this['table']) {
            $export = new DeferredFile(
                'table_' . $this->uuid() . '.xlsx',
                function (DeferredFile $file) {
                    FS::touch($file->path());
                    $writer = new SpreadsheetWriter();
                    $data = $this['table'];
                    // add header
                    if ($data['head']) {
                        $writer->writeHeaders(
                            array_map(
                                function (array $cell) {
                                    return $cell['cell'];
                                },
                                $data['head'][0]['row']
                            )
                        );
                    }
                    // add the rest of the rows
                    foreach ($data['body'] as $row) {
                        $writer->writeRow(
                            array_map(
                                function (array $cell) {
                                    return $cell['cell'];
                                },
                                $row['row']
                            )
                        );
                    }
                    // save file
                    (new Xlsx($writer->spreadsheet()))
                        ->save($file->path());
                },
                $this->uuid() . '/export',
                60
            );
            $form->addChild(
                sprintf(
                    '<div><a href="%s">Export source to edit in Excel</a></div>',
                    $export->url()
                )
            );
        }

        // file uploading field
        $file = (new Field('Upload file', new UploadSingle()))
            ->setID('rich-table-file-field')
            ->addTip('Uploading a spreadsheet will entirely replace all table content, including row/cell IDs, which means existing subset embeds will break if you use this feature')
            ->addTip('First row will be used as headers')
            ->addForm($form);

        // special validators to ensure file is require when file mode is selected
        $file->addValidator(function () use ($file, $mode) {
            if (!$file->value() && $mode->value() == 'url') {
                return "This field is required";
            } else return null;
        });

        // special scripting for front end visibility
        $form->__toString();
        $edit_mode_id = $mode->field('edit')->input()->id();
        $file_mode_id = $mode->field('file')->input()->id();
        $edit_field = $table->id();
        $file_field = $file->id();
        $form->addChild(<<<SCRIPT
            <script>
                (() => {
                    // get elements
                    var edit = document.getElementById('$edit_mode_id');
                    var file = document.getElementById('$file_mode_id');
                    var edit_field = document.getElementById('$edit_field');
                    var file_field = document.getElementById('$file_field');
                    // add event listeners
                    edit.addEventListener('change', checkStatus);
                    file.addEventListener('change', checkStatus);
                    // do initial check
                    checkStatus();
                    // status checking 
                    function checkStatus() {
                        edit_field.style.display = edit.checked ? null : 'none';
                        file_field.style.display = file.checked ? null : 'none';
                    }
                })();
            </script>
            SCRIPT);

        // callback to set values
        $form->addCallback(function () use ($name, $mode, $table, $file) {
            $this->name($name->value());
            if ($mode->value() == 'file') {
                $f = $file->value();
                $this->setTableFromFile($f['tmp_name'], pathinfo($f['name'], PATHINFO_EXTENSION));
            } else {
                unset($this['table']);
                $this['table'] = $table->value();
            }
        });
    }

    public function shortCode(ShortcodeInterface $code): ?string
    {
        return $this->render();
    }

    public static function className(): string
    {
        return 'Table';
    }

    public static function description(): string
    {
        return 'Rich editor for tables, including the option to upload a spreadsheet';
    }

    protected function setTableFromFile(string $path, $extension = null)
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