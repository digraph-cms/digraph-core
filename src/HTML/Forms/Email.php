<?php

namespace DigraphCMS\HTML\Forms;

class Email extends INPUT
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);
        $this->addValidator(function () {
            if (!$this->value()) return null;
            return filter_var($this->value(), FILTER_VALIDATE_EMAIL);
        });
    }

    public function value($useDefault = false)
    {
        return strtolower(parent::value($useDefault));
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'email'
            ]
        );
    }
}
