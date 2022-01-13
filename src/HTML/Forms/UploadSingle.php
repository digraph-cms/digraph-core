<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;

class UploadSingle extends INPUT
{
    protected $filestore;

    public function __construct()
    {
        $this->setAttribute('type', 'file');
    }

    public function setForm(FormWrapper $form)
    {
        $form->form()->setAttribute('enctype', 'multipart/form-data');
        return parent::setForm($form);
    }

    public function value($useDefault = false): ?array
    {
        if (isset($_FILES[$this->id()]) && $_FILES[$this->id()]['error'] == 0) {
            return $_FILES[$this->id()];
        } else {
            return null;
        }
    }

    public function filestore(string $media_uuid): FilestoreFile
    {
        if (!$this->filestore && $this->value()) {
            $this->filestore = Filestore::upload(
                $this->value()['tmp_name'],
                $this->value()['name'],
                $media_uuid,
                []
            );
        }
        return @$this->filestore;
    }
}
