<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data\Structures;

class Stack extends AbstractQueueLikeStructure
{
    /**
     * how to sort items when retrieving them for pull/peek
     */
    const SORT = 'data_id DESC';
}
