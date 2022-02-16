<?php

namespace DigraphCMS\RichContent;

use ParsedownExtra;

class ParsedownDigraph extends ParsedownExtra
{
    public function __construct()
    {
        parent::__construct();
        $this->inlineMarkerList .= '=';
        $this->InlineTypes['='] = ['Highlight'];
    }

    protected function inlineHighlight($Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
        }

        if ($Excerpt['text'][1] === '=' and preg_match('/^==(.+?)==(?!=)/us', $Excerpt['text'], $matches)) {
            return [
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'mark',
                    'handler' => 'line',
                    'text' => $matches[1]
                ),
            ];
        }
    }
}
