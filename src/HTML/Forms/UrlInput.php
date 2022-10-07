<?php

namespace DigraphCMS\HTML\Forms;

class UrlInput extends INPUT
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);
        $this->addValidator(function () {
            if (!$this->value()) return null;
            return filter_var($this->value(), FILTER_VALIDATE_URL)
                ? null
                : "Please enter a valid URL (don't forget the leading <code>http://</code> or <code>http://</code>)";
        });
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'url'
            ]
        );
    }
}
