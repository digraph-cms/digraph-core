<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Downtime;

use Digraph\Helpers\AbstractHelper;

class DowntimeHelper extends AbstractHelper
{
    protected $current = false;
    protected $prenotifications = false;

    public function factory()
    {
        return $this->cms->factory('downtime');
    }

    public function current()
    {
        if ($this->current === false) {
            $search = $this->factory()->search();
            $search->where('${downtime.start} <= :time AND (${downtime.end} is null OR ${downtime.end} >= :time)');
            $search->order('${downtime.end} desc');
            $search->limit(1);
            if ($result = $search->execute(['time' => time()])) {
                $this->current = $result[0];
            } else {
                $this->current = null;
            }
        }
        return $this->current;
    }

    public function prenotifications()
    {
        if ($this->current === false) {
            $search = $this->factory()->search();
            $search->where('${downtime.start} > :time AND ${downtime.prenotify} is not null AND ${downtime.prenotify} <= :time');
            $search->order('${downtime.start} asc');
            $this->prenotifications = $search->execute(['time' => time()]);
        }
        return $this->prenotifications;
    }
}
