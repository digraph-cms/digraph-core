<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions\Parts\Chunks;

use Formward\Form;

class SubmissionMeta extends AbstractChunk
{
    public function body_form() : Form
    {
        $form = $this->form();
        $class = $this->submission()->submissionFieldClass();
        $form['submission'] = new $class('');
        $form['submission']->default($this->submission()['submission']);
        return $form;
    }

    public function form_handle(Form &$form)
    {
        $this->submission()['submission'] = $form['submission']->value();
        $this->submission()->update();
    }

    public function complete()
    {
        return true;
    }
    
    public function body_complete()
    {
        echo "<table>";
        foreach ($this->submission()['submission'] as $key => $value) {
            echo "<tr>";
            echo "<td>$key</td>";
            echo "<td>$value</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}