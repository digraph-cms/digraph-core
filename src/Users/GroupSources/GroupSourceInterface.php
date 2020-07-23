<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\GroupSources;

use Digraph\CMS;

interface GroupSourceInterface
{
    public function __construct(CMS $cms);
    public function groups(string $id) : ?array;
}
