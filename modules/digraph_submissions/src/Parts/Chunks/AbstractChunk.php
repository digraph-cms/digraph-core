<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions\Parts\Chunks;

use Digraph\Modules\digraph_submissions\Parts\AbstractPartsClass;
use Formward\Form;

abstract class AbstractChunk
{
    protected $parts;
    protected $name;
    protected $label;

    abstract public function body_complete();
    abstract public function body_form() : Form;
    abstract public function form_handle(Form &$form);

    protected function form()
    {
        return new Form('', md5(serialize([$this->name,$this->label])));
    }

    public function complete()
    {
        return isset($this->submission()[$this->name]);
    }

    public function body_edit()
    {
        $form = $this->body_form();
        if ($this->complete()) {
            $form->submitButton()->label('Save changes');
        } else {
            $form->submitButton()->label('Save section');
        }
        if ($form->handle()) {
            $this->form_handle($form);
            $url = $this->submission()->url('chunk', [
                'chunk' => $this->name
            ], true);
            header('Location: '.$url);
            exit();
        }
        echo $form;
    }

    public function body_incomplete()
    {
        echo "<em>section incomplete</em>";
    }

    public function submission()
    {
        return $this->parts->submission();
    }

    public function body($disableEdit=false)
    {
        ob_start();
        $mode = ($this->complete()?'complete':'incomplete');
        if ($mode == 'incomplete') {
            if ($this->submission()->isEditable()) {
                $mode .= ' editing';
            }
        } elseif (@$_GET['edit']) {
            $mode .= ' editing';
        }
        echo "<div class='submission-chunk $mode'>";
        echo "<div class='submission-chunk-label'>".$this->label."</div>";
        if (!$disableEdit && $this->submission()->isEditable() && (!$this->complete() || @$_GET['edit'])) {
            //display editing form if editing is allowed and either incomplete or edit requested
            echo $this->body_edit();
            //display cancel link
            if ($this->complete()) {
                $url = $this->submission()->url('chunk', [
                    'chunk' => $this->name
                ], true);
                echo "<a class='mode-switch' href='$url'>Cancel editing</a>";
            }
        } elseif ($this->complete()) {
            //display complete content if completed
            echo $this->body_complete();
            //display edit link
            if ($this->submission()->isEditable()) {
                $url = $this->submission()->url('chunk', [
                    'chunk' => $this->name,
                    'edit' => true
                ], true);
                if (!$disableEdit) {
                    echo "<a class='mode-switch' href='$url'>Edit section</a>";
                }
            }
        } else {
            //display incomplete content if incomplete and not editable
            echo $this->body_incomplete();
        }
        echo "</div>";
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    public function __construct(AbstractPartsClass &$parts, string $name, string $label)
    {
        $this->parts = $parts;
        $this->name = $name;
        $this->label = $label;
    }
}
