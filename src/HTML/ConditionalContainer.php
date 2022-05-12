<?php

namespace DigraphCMS\HTML;

/**
 * Use to create DIV tags that only render anything if
 * they have child content.
 */
class ConditionalContainer extends DIV
{
    public function toString(): string
    {
        if (!array_filter($this->children(), function ($node) {
            if ($node instanceof Node) return !$node->hidden();
            else return !!$node;
        })) {
            return "";
        } else {
            return parent::toString();
        }
    }
}
