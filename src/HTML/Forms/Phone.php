<?php

namespace DigraphCMS\HTML\Forms;

class Phone extends INPUT
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);
        $this->addValidator(function () {
            if (!$this->value()) return null;
            return preg_match('/^(\([0-9]{3}\) )?[0-9]{3}\-[0-9]{4}$/', $this->value())
                ? null
                : "Please enter a valid phone number (either 10 digits with an area code or 7 digits without)";
        });
    }

    public function value($useDefault = false)
    {
        /** @var string */
        $value = preg_replace('/[^0-9]/', '', parent::value($useDefault) ?? '') ?? '';
        if (strlen($value) == 7) return sprintf('%s-%s', substr($value, 0, 3), substr($value, 3, 4));
        elseif (strlen($value) == 10) return sprintf('(%s) %s-%s', substr($value, 0, 3), substr($value, 3, 3), substr($value, 6, 4));
        else return $value ? $value : null;
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'tel'
            ]
        );
    }
}
