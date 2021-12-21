<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\ConditionalContainer;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\SMALL;
use DigraphCMS\HTML\Text;

class Field extends DIV implements InputInterface
{
    protected $input;
    protected $label;
    protected $tips;
    protected $validationMessage;
    protected $validationMessageText;

    public function __construct(string $label, InputInterface $input = null)
    {
        $this->label = new LABEL($label);
        $this->setInput($input ?? new INPUT());
        $this->addClass('form-field');
    }

    public function validationError(): ?string
    {
        if ($message = $this->input()->validationError()) {
            $this->validationMessageText()->setContent($message);
            $this->validationMessage()->setHidden(false);
            return $message;
        } else {
            $this->validationMessageText()->setContent('');
            $this->validationMessage()->setHidden(true);
            return null;
        }
    }

    public function validationMessageText(): Text
    {
        if (!$this->validationMessageText) {
            $this->validationMessageText = new Text('');
            $this->validationMessage()->addChild($this->validationMessageText);
        }
        return $this->validationMessageText;
    }

    public function children(): array
    {
        $children = array_merge(
            [
                $this->label(),
                $this->input()
            ],
            parent::children()
        );
        if ($this->submitted()) {
            $children[] = $this->validationMessage();
        }
        $children[] = $this->tips();
        return $children;
    }

    public function classes(): array
    {
        $classes = parent::classes();
        if ($this->submitted()) {
            if ($this->validationError()) {
                $classes[] = 'form-field--error';
            }
        }
        return $classes;
    }

    public function validationMessage(): ConditionalContainer
    {
        if (!$this->validationMessage) {
            $this->validationMessage = (new ConditionalContainer())
                ->addClass('form-field__error-message');
        }
        return $this->validationMessage;
    }

    public function setInput(InputInterface $input)
    {
        $this->input = $input;
        $this->label->setFor($this->input);
    }

    public function input(): InputInterface
    {
        return $this->input;
    }

    public function label(): LABEL
    {
        return $this->label;
    }

    public function tips(): ConditionalContainer
    {
        if (!$this->tips) {
            $this->tips = new ConditionalContainer();
            $this->tips->addClass('form-field__tips');
        }
        return $this->tips;
    }

    public function addTip(string $tip)
    {
        $this->tips()->addChild(
            (new SMALL())
                ->addChild(new Text($tip))
                ->addClass('form-field__tips__tip')
        );
    }

    public function form(): ?FORM
    {
        return $this->input()->form();
    }

    public function submitted(): bool
    {
        return $this->input()->submitted();
    }

    public function setForm(FORM $form)
    {
        $this->input()->setForm($form);
    }

    public function required(): bool
    {
        return $this->input()->required();
    }

    public function default()
    {
        return $this->input()->default();
    }

    public function value($useDefault = false)
    {
        return $this->input()->value($useDefault);
    }

    public function setRequired(bool $required, string $message = null)
    {
        $this->input()->setRequired($required, $message);
        return $this;
    }

    public function setDefault($default)
    {
        $this->input()->setDefault($default);
        return $this;
    }

    public function setValue($value)
    {
        $this->input()->setValue($value);
        return $this;
    }
}
