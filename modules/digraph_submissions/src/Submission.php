<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions;

use Digraph\DSO\Noun;

class Submission extends Noun
{
    const ROUTING_NOUNS = ['submission'];
    const FILESTORE = true;

    protected $parts;

    public function actions($links)
    {
        $links['files'] = '!id/allfiles';
        return $links;
    }

    public function complete()
    {
        return $this->parts()->complete();
    }

    public function &window()
    {
        if ($this->parent() instanceof SubmissionWindow) {
            return $this->parent();
        }
        return null;
    }

    public function &parts()
    {
        if (!$this->parts) {
            $class = $this->partsClass();
            $this->parts = new $class($this);
        }
        return $this->parts;
    }

    public function submitterFieldClass()
    {
        if ($this->window()) {
            return $this->window()->submitterFieldClass();
        }
        return SubmitterField::class;
    }

    public function submissionFieldClass()
    {
        if ($this->window()) {
            return $this->window()->submissionFieldClass();
        }
        return SubmissionField::class;
    }

    public function partsClass()
    {
        if ($this->window()) {
            return $this->window()->partsClass();
        }
        return Parts\EmptyPartsClass::class;
    }

    public function isViewable()
    {
        //if user can edit, they can view
        if ($this->isEditable()) {
            return true;
        }
        //owner can view
        if ($this->cms()->helper('users')->id() == $this['owner']) {
            return true;
        }
        //permissions via type
        if ($this->cms()->helper('permissions')->check($this['dso.type'].'/view')) {
            return true;
        }
        //default false
        return false;
    }

    public function isEditable()
    {
        //parent isEditable allows access
        if (parent::isEditable()) {
            return true;
        }
        //permissions via type
        if ($this->cms()->helper('permissions')->check($this['dso.type'].'/edit')) {
            return true;
        }
        //default false
        return false;
    }

    public function name($verb=null)
    {
        return implode(' ', [
            $this['submitter.firstname'],
            $this['submitter.lastname'].',',
            $this->cms()->helper('strings')->date($this['dso.created.date']),
            '(#'.$this['dso.id'].')'
        ]);
    }

    public function hook_postEditUrl()
    {
        return $this->url('display', null)->string();
    }

    public function hook_postAddUrl()
    {
        return $this->url('display', null)->string();
    }

    public function parentEdgeType(&$parent)
    {
        if ($parent instanceof SubmissionWindow) {
            return 'submission';
        }
        return null;
    }

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        //hide built-in fields
        $map['digraph_name'] = false;
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        //owner is a hidden field
        $map['owner'] = [
            'label' => 'Submission owner',
            'class' => '\\Formward\\Fields\\Hidden',
            'field' => 'owner',
            'default' => $this->cms()->helper('users')->id(),
            'required' => true,
            'tips' => ['The owner is the only non-administrator able to view or manage this submission, and will be emailed submission confirmations and updates.']
        ];
        //owner if user has submissions/setowner permission
        if ($this->cms()->helper('permissions')->check('submissions/setowner')) {
            $map['owner']['class'] = 'user';
        }
        //submitter info
        $map['submitter'] = [
            'label' => 'Submitter information',
            'class' => ($this['formclass.submitter']?$this['formclass.submitter']:$this->submitterFieldClass()),
            'field' => 'submitter'
        ];
        //first-step submission data
        $map['submission'] = [
            'label' => 'Submission information',
            'class' => ($this['formclass.submission']?$this['formclass.submission']:$this->submissionFieldClass()),
            'field' => 'submission'
        ];
        return $map;
    }
}
