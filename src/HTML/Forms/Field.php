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

    public function disabled(): bool
    {
        return $this->input()->disabled();
    }

    public function setDisabled(bool $disabled): static
    {
        $this->input()->setDisabled($disabled);
        return $this;
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

    /**
     * Set a validator function for this input. Callable should return a string with an
     * error message if invalid, or otherwise null.
     *
     * @param callable $validator
     * @return static
     */
    public function addValidator(callable $validator): static
    {
        $this->input()->addValidator($validator);
        return $this;
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
        if ($this->required()) {
            $classes[] = 'form-field--required';
        }
        if ($this->disabled()) {
            $classes[] = 'form-field--disabled';
        }
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

    public function setInput(InputInterface $input): static
    {
        $this->input = $input;
        $this->label->setFor($this->input);
        return $this;
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

    /**
     * @param string $tip
     * @return static
     */
    public function addTip(string $tip): static
    {
        $this->tips()->addChild(
            (new SMALL())
                ->addChild(new Text($tip))
                ->addClass('form-field__tips__tip')
        );
        return $this;
    }

    public function form(): ?FormWrapper
    {
        return $this->input()->form();
    }

    public function submitted(): bool
    {
        return $this->input()->submitted();
    }

    public function addForm(FormWrapper $form): static
    {
        $form->addChild($this);
        return $this;
    }

    public function setForm(FormWrapper $form): static
    {
        $this->input()->setForm($form);
        return $this;
    }

    public function required(): bool
    {
        return $this->input()->required();
    }

    public function default(): mixed
    {
        return $this->input()->default();
    }

    public function value(bool $useDefault = false): mixed
    {
        return $this->input()->value($useDefault);
    }

    public function setRequired(bool $required, string $message = null): static
    {
        $this->input()->setRequired($required, $message);
        return $this;
    }

    public function setDefault(mixed $default): static
    {
        $this->input()->setDefault($default);
        return $this;
    }

    public function setValue(mixed $value): static
    {
        $this->input()->setValue($value);
        return $this;
    }
}
