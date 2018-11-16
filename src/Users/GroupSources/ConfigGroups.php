<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\GroupSources;

class ConfigGroups extends AbstractGroupSource
{
    public function groups(string $id) : ?array
    {
        $groups = [];
        //load groups from config
        if (isset($this->cms->config['groups.'.$id])) {
            $groups = $this->cms->config['groups.'.$id];
        }
        return $groups;
    }
}
