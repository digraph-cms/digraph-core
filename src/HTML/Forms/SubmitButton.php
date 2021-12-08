<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\Tag;

class SubmitButton extends Tag
{
    protected $tag = 'input';
    protected $void = true;

    protected $text = 'Submit';

    public function text(): string {
        return $this->text;
    }

    /**
     * Change the text displayed on button
     *
     * @param string $text
     * @return $this
     */
    public function setText(string $text) {
        $this->text = $text;
        return $this;
    }

    public function attributes(): array
    {
        return array_merge(
            parent::attributes(),
            [
                'type' => 'submit',
                'value' => $this->text()
            ]
        );
    }
}
