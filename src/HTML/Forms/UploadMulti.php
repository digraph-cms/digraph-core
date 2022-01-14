<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;

class UploadMulti extends INPUT
{
    protected $filestore;
    protected $value;

    public function __construct()
    {
        $this->setAttribute('type', 'file');
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'file',
                'multiple' => null,
                'name' => parent::attributes()['name'] . '[]'
            ]
        );
    }

    public function setForm(FormWrapper $form)
    {
        $form->form()->setAttribute('enctype', 'multipart/form-data');
        return parent::setForm($form);
    }

    public function submittedValue(): array
    {
        return @$_FILES[$this->id()] ?? [];
    }

    public function value($useDefault = false): array
    {
        if ($this->value === null) {
            $this->value = [];
            foreach ($this->submittedValue() as $key => $values) {
                foreach ($values as $i => $value) {
                    @$this->value[$i][$key] = $value;
                }
            }
            $this->value = array_filter(
                $this->value,
                function ($e) {
                    return $e['error'] != 4;
                }
            );
        }
        return $this->value;
    }

    /**
     * Undocumented function
     *
     * @param string $media_uuid
     * @return FilestoreFile[]
     */
    public function filestore(string $media_uuid): array
    {
        if ($this->filestore === null) {

            $this->filestore = array_map(
                function (array $f) use ($media_uuid): FilestoreFile {
                    return Filestore::upload(
                        $f['tmp_name'],
                        $f['name'],
                        $media_uuid,
                        []
                    );
                },
                $this->value()
            );
        }
        return @$this->filestore;
    }
}
