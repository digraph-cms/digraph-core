<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions\Parts\Chunks;

use Formward\Form;

class Upload extends AbstractChunk
{
    public function upload_field()
    {
        $field = new \Formward\Fields\File('Upload');
        $field->required(true);
        return $field;
    }

    public function body_form() : Form
    {
        $form = $this->form();
        $form['upload'] = $this->upload_field();
        return $form;
    }

    public function form_handle(Form &$form)
    {
        $submission = $this->submission();
        $fs = $submission->cms()->helper('filestore');
        $uniqid = $fs->import($submission,$form['upload']->value());
        $submission[$this->name] = $uniqid;
        $submission->update();
    }

    public function body_complete()
    {
        $fs = $this->submission()->cms()->helper('filestore');
        $files = $fs->get($this->submission(), $this->submission()[$this->name]);
        echo "<div>";
        foreach ($files as $file) {
            echo $file->metaCard();
        }
        echo "</div>";
    }
}