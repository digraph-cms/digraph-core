<?php

namespace DigraphCMS\HTML\Forms\Fields\Autocomplete;

/**
 * @method PageInput input() Get the input element for this field
 */
class PageField extends AutocompleteField
{
    public function __construct(string $label)
    {
        parent::__construct(
            $label,
            new PageInput()
        );
        $this->addClass('autocomplete-field--page');
    }

    /**
     * Set the page class to limit results to. Uses the internal name, such as what is returned by AbstractPage::class()
     */
    public function setPageClass(string $class): static
    {
        $input = $this->input();
        $input->setPageClass($class);
        return $this;
    }
}