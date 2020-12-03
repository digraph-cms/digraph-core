<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

class Noun extends AbstractAutocomplete
{
    const SOURCE = 'noun';

    public function limitTypes(array $types)
    {
        $this->srcArg('types', implode(',', $types));
    }
}
