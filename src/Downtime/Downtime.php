<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Downtime;

use Destructr\Factory;
use Digraph\DSO\Noun;

class Downtime extends Noun
{
    public function __construct(array $data = null, Factory $factory = null)
    {
        parent::__construct($data, $factory);
    }

    public function url(?string $verb = null, ?array $args = null, bool $canonical = false)
    {
        $url = $this->factory->cms()->helper('urls')->url(
            '_downtime',
            'edit',
            ['id' => $this['dso.id']]
        );
        $url['text'] = $this->name();
        return $url;
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        $map['downtime_start'] = [
            'weight' => 200,
            'field' => 'downtime.start',
            'label' => 'Start downtime at',
            'class' => 'datetime',
            'required' => true,
        ];
        $map['downtime_end'] = [
            'weight' => 200,
            'field' => 'downtime.end',
            'label' => 'End downtime at',
            'class' => 'datetime',
            'required' => false,
        ];
        $map['downtime_prenotify'] = [
            'weight' => 200,
            'field' => 'downtime.prenotify',
            'label' => 'Prenotification start',
            'class' => 'datetime',
            'required' => false,
        ];
        return $map;
    }
}
