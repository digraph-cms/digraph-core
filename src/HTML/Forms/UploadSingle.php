<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;

class UploadSingle extends INPUT
{
    protected $filestore;

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'file',
                'value' => null
            ]
        );
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

    public function filestore(string $media_uuid, null|callable $permissions = null, string|null $filename = null): FilestoreFile
    {
        if (!$this->filestore && $this->value()) {
            $this->filestore = Filestore::upload(
                $this->value()['tmp_name'],
                $filename ?? $this->value()['name'],
                $media_uuid,
                [],
                $permissions
            );
        }
        return @$this->filestore;
    }
}