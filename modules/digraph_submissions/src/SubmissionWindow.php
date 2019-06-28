<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_submissions;

use Digraph\DSO\Noun;

class SubmissionWindow extends Noun
{
    const ROUTING_NOUNS = ['submission-window'];
    const TEMPLATE = 'pages/submission-window.twig';

    public function submissionType()
    {
        return 'submission';
    }

    public function canSubmit()
    {
        if ($this->isEditable()) {
            return true;
        }
        if (!$this->open()) {
            return false;
        }
        if (!$this->cms()->helper('users')->user()) {
            return false;
        }
        return $this->cms()->helper('permissions')
            ->check('submission/submit', 'submissions');
    }

    public function allSubmissions()
    {
        //get list of child IDs
        $childIDs = $this->cms()->helper('graph')->childIDs(
            $this['dso.id'],
            'submission'
        );
        if (!$childIDs) {
            //short-circuit if there are no child IDs
            return [];
        }
        $childIDs = '${dso.id} in ("'.implode('","', $childIDs).'")';
        //set up search and execute
        $search = $this->cms()->factory()->search();
        $search->where('${dso.type} = :type AND '.$childIDs);
        $search->order('${dso.created.date} desc');
        return $search->execute([
            ':type' => $this->submissionType()
        ]);
    }

    public function mySubmissions()
    {
        if (!$this->cms()->helper('users')->user()) {
            return [];
        }
        //get list of child IDs
        $childIDs = $this->cms()->helper('graph')->childIDs(
            $this['dso.id'],
            'submission'
        );
        if (!$childIDs) {
            //short-circuit if there are no child IDs
            return [];
        }
        $childIDs = '${dso.id} in ("'.implode('","', $childIDs).'")';
        //set up search and execute
        $search = $this->cms()->factory()->search();
        $search->where('${dso.type} = :type AND ${owner} = :owner AND '.$childIDs);
        $search->order('${dso.created.date} desc');
        return $search->execute([
            ':type' => $this->submissionType(),
            ':owner' => $this->cms()->helper('users')->id()
        ]);
    }

    public function pending()
    {
        if (!$this->start()) {
            return false;
        }
        return time() < $this['window.start'];
    }

    public function ended()
    {
        if (!$this->end()) {
            return false;
        }
        return time() > $this['window.end'];
    }

    public function open()
    {
        return !$this->pending() && !$this->ended();
    }

    public function startHR()
    {
        if ($this['window.start']) {
            return $this->cms()->helper('strings')
                ->datetime($this['window.start']);
        }
        return null;
    }

    public function endHR()
    {
        if ($this['window.end']) {
            return $this->cms()->helper('strings')
                ->datetime($this['window.end']);
        }
        return null;
    }

    public function start()
    {
        return $this['window.start'];
    }

    public function end()
    {
        return $this['window.end'];
    }

    public function bodyText()
    {
        return parent::body();
    }

    public function body()
    {
        return $this->cms()->helper('templates')
            ->render(
                static::TEMPLATE,
                [
                    'window' => $this
                ]
            );
    }

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        // date
        $map['window_start'] = [
            'label' => 'Submission start date',
            'class' => 'datetime',
            'required' => false,
            'field' => 'window.start',
            'weight' => 150,
            'tips' => ['If no start date is specified the window will open immediately.']
        ];
        $map['window_end'] = [
            'label' => 'Submission end date',
            'class' => 'datetime',
            'required' => false,
            'field' => 'window.end',
            'weight' => 151,
            'tips' => ['If no end date is specified the window will remain open indefinitely.']
        ];
        return $map;
    }
}
