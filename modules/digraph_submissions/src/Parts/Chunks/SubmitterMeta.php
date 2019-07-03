<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions\Parts\Chunks;

use Formward\Form;

class SubmitterMeta extends AbstractChunk
{
    public function body_form() : Form
    {
        $form = $this->form();
        $class = $this->submission()->submitterFieldClass();
        $form['submitter'] = new $class('');
        $form['submitter']->default($this->submission()['submitter']);
        return $form;
    }

    public function form_handle(Form &$form)
    {
        $this->submission()['submitter'] = $form['submitter']->value();
        $this->submission()->update();
    }

    public function complete()
    {
        return true;
    }
    
    public function body_complete()
    {
        echo "<table>";
        foreach ($this->submission()['submitter'] as $key => $value) {
            echo "<tr>";
            echo "<td>$key</td>";
            echo "<td>$value</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}