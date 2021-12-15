<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\TEXTAREA;

class RichContentField extends Field
{
    public function setDefault($default)
    {
        if ($default instanceof RichContent) {
            $default = $default->value();
        }
        parent::setDefault($default);
        return $this;
    }

    public function value($useDefault = false): ?RichContent
    {
        return new RichContent($this->input()->value($useDefault));
    }

    public function default(): ?RichContent
    {
        return new RichContent($this->input()->default());
    }

    public function __construct(string $label)
    {
        parent::__construct($label, new TEXTAREA());
    }
}
