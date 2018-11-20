<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_block_types;

use Digraph\DSO\Noun;
use Digraph\FileStore\FileStoreFile;

class BlockArea extends Noun
{
    const ROUTING_NOUNS = ['blockarea'];

    public function body()
    {
        return $this->blockContent();
    }

    public function name($verb = null)
    {
        return '[blockarea:'.parent::name().']';
    }

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '001_digraph_title' => false,
            '500_digraph_body' => false,
            '900_digraph_published' => false
        ];
    }

    public function blockContent()
    {
        $out = '';
        $b = $this->factory->cms()->helper('blocks');
        foreach ($this->children() as $block) {
            $out .= $b->block($block);
        }
        return $out;
    }
}
