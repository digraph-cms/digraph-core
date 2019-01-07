<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Blocks;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;

class BlockHelper extends AbstractHelper
{
    public function block($noun)
    {
        $s = $this->cms->helper('strings');
        if (is_string($noun)) {
            $noun = $this->cms->read($noun);
        }
        if (!$noun) {
            $output = '<div class="digraph-block-content">['.$s->string('blocks.notfound').']</div>';
        } else {
            $output = '<div class="digraph-actionbar inactive" data-id="'.$noun['dso.id'].'">'.
                '<div class="digraph-actionbar-title">'.$noun->name().'</div></div>'.
                '<div class="digraph-block-content">'.$this->blockContent($noun).'</div>';
        }
        return '<div class="digraph-block">'.$output.'</div>';
    }

    public function exists($noun)
    {
        if (is_string($noun)) {
            $noun = $this->cms->read($noun);
        }
        if (!$noun) {
            return false;
        }
        return $noun['dso.type'] == 'block' || ($noun['dso.type'] == 'blockarea' && $noun->children());
    }

    public function blockContent($noun)
    {
        if (method_exists($noun, 'blockContent')) {
            return $noun->blockContent();
        }
        return $noun->body();
    }
}
